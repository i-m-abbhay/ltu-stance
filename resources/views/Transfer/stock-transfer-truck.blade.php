@extends('layouts.mobile')
@section('title', '在庫移管')

@section('content')


<stocktransfertruck-component :shelfnumberList="{{ $shelfnumberList }}"></stocktransfertruck-component>


@endsection