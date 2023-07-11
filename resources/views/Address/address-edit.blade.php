@extends('layouts.default')
@section('title', '住所情報登録')

<script src="{{ url('./js/yubinbango-core.js') }}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRYQj4Z6N9YcJNaNrE7Mc39OXtZBMDgzQ&callback=initMap" async defer></script>


@section('content')

<addressedit-component 
    :addressdata="{{ $addressData }}"
></addressedit-component>

@endsection