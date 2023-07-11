@extends('layouts.mobile')
@section('title', '在庫移管')

@section('content')


<stocktransfersearch-component 
    :warehouseList="{{ $warehouseList }}" 
></stocktransfersearch-component>


@endsection