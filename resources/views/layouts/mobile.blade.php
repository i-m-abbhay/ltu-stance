@extends('icons.svg-icons')
<!DOCTYPE html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta http-equiv="content-language" content="ja"> 
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1.0,user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title')</title>

    <link rel="shortcut icon" href="{{ url('/favicon.ico') }}">
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
    <link rel="stylesheet" href="{{ url('./css/style.css?') . \Carbon\Carbon::now() }}">
    
    <script src="{{ url('./js/const.js?') . \Carbon\Carbon::now() }}"></script>
    <script src="{{ url('./js/moment.min.js') }}"></script>

    <style>
        .flash-message {
            display: block;
            width: 100%;
            height: 35px;
            margin: 0;
            padding: 4px 10px;
            color: #fff;
            font-size: 16px;
        }
        .btn-close-info {
            border: none;
            background-color: inherit;
            color: #fff;
        }
        .contents-common {
            margin: 10px;
        }
        .sidemenu-bar {
            position: absolute;
            top: 0;
            right: 0;
            /* left: 0; */
            z-index: 10;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="whole">                
            <div class="mobile-header">
                <mobileheader-component :loginuser="{{ \Auth::user() }}"></mobileheader-component>
                <p class="header-text">@yield('title')</p> 
            </div>
            @if (session('flash_error'))
                <div class="flash-message label-danger">
                    <button type="button" class="btn-close-info" onClick="closeInfo(this);">×</button>
                    {{ session('flash_error') }}
                </div>
            @endif
            <div class="contents-common">
                @yield('content')
            </div>
        </div>
    </div>
    
    <script>
        // flash情報バー削除
        function closeInfo(ele) {
            ele.parentNode.remove();
        }
    </script>
    
    <script src=" {{ mix('js/app.js') }} "></script>
</body>