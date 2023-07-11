<!DOCTYPE html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta http-equiv="content-language" content="ja"> 
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <link rel="shortcut icon" href="{{ url('/favicon.ico') }}">
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
    <link rel="stylesheet" href="{{ url('./css/style.css') }}">

    <script src="{{ url('./js/const.js?') . \Carbon\Carbon::now() }}"></script>
    <script src="{{ url('./js/moment.min.js') }}"></script>
    <style>
        
    </style>
</head>
<body>
    <div id="app">
        <div class="whole">
            <div class="contents-common">
                @yield('content')
            </div>
        </div>
    </div>
    
    <script src=" {{ mix('js/app.js') }} "></script>
</body>