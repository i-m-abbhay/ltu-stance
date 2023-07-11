@extends('layouts.default')
{{-- @section('title', '見積書') --}}

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>見積書</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- <link rel="stylesheet" href="{{ url('./css/ar-js-viewer.css?') . \Carbon\Carbon::now() }}"> -->
<link rel="stylesheet" href="{{ url('./css/ActiveReportsV2/ar-js-viewer.css?20210408') }}">
<link rel="stylesheet" href="{{ url('./css/ActiveReportsV2/ar-js-ui.css?20210408') }}">
@section('content')

<quotereport-component 
:quote-version="{{ $quoteVersion }}" 
:data-source="{{ $dataSource }}" 
></quotereport-component>

@endsection