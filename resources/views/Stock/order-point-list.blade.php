@extends('layouts.default')
@section('title', '倉庫別在庫管理')

@section('content')

<orderpointlist-component 
        :baselist="{{ $baseList }}"
></orderpointlist-component>

@endsection


</script>