@extends('layouts.default')
@section('title', '在庫移管一覧')

@section('content')

<stocktransferlist-component 
    :warehouselist="{{ $warehouseList }}"
    :qrcodelist="{{ $qrcodeList }}"
></stocktransferlist-component>

@endsection
