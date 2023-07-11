@extends('layouts.default')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>発注書</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- <link rel="stylesheet" href="{{ url('./css/ar-js-viewer.css?') . \Carbon\Carbon::now() }}"> -->
<link rel="stylesheet" href="{{ url('./css/ActiveReportsV2/ar-js-viewer.css?20210409') }}">
<link rel="stylesheet" href="{{ url('./css/ActiveReportsV2/ar-js-ui.css?20210409') }}">
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRYQj4Z6N9YcJNaNrE7Mc39OXtZBMDgzQ&callback=initMap" async defer></script>

@section('content')

<orderreport-component 
:order="{{ $order }}" 
:data-source="{{ $dataSource }}" 
:addressdata="{{ $addressData }}"
></orderreport-component>

@endsection