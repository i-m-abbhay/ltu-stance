@extends('layouts.default')
@section('title', '在庫照会')

@section('content')

<stocksearch-component 
        :warehouselist="{{ $warehouseList }}"
        :supplierlist="{{ $supplierList }}"
        :shelflist="{{ $shelfList }}"
        :customerlist="{{ $customerList }}"
        :matterlist="{{ $matterList }}"
></stocksearch-component>

@endsection


</script>