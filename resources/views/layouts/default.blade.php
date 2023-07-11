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

    <!-- <script src="{{ url('./js/ActiveReports/ar-js-core.js') }}"></script>
    <script src="{{ url('./js/ActiveReports/ar-js-viewer.js') }}"></script>
    <script src="{{ url('./js/ActiveReports/ar-js-pdf.js') }}"></script>
    <script src="{{ url('./js/ActiveReports//ar-js-locales.js') }}"></script> -->
    <script src="{{ url('./js/ActiveReportsV2/ar-js-core.js') }}"></script>
    <script src="{{ url('./js/ActiveReportsV2/ar-js-viewer.js') }}"></script>
    <script src="{{ url('./js/ActiveReportsV2/ar-js-pdf.js') }}"></script>
    <script src="{{ url('./js/ActiveReportsV2/ar-js-designer.js') }}"></script>
    <script src="{{ url('./js/ActiveReportsV2/ar-js-xlsx.js') }}"></script>
    <script src="{{ url('./js/ActiveReportsV2/ar-js-html.js') }}"></script>
    <script src="{{ url('./js/ActiveReportsV2/locales/ar-js-locales.js') }}"></script>
    <script>
        // GC.ActiveReports.Core.PageReport.LicenseKey = 'sf-system.cloud,456112884867786#B0foRUylEeRhldxwWYZhnc9l4d6siYLpnMH5mMLNjUTZ4doVjU8cnMsx6NElTTwoUUYhENKNnbnB5UapXRwNEM5BTRqtmQ7YncHJmeCJ5ao3CMDV5SyU4ZZ9WQBxmSI9ES8xUQmJlSmBHUWt4YxEWTw3Ge4hXSyoFbQd7TqV5b4JGe6RFaOB7dwMGb8ZDMlJnbXRDWRllWrRlRplUZhZjcX54R53SRZlzd5hHaIRkbnJmTIJWeGR5cOVnTFNFNxdHcrBFMMRkMOljSvh7RnVGWSFWekRXb7FWappETJd7QilWYL3WZr3kMvUVSrVjd7RkI0IyUiwiICVTNGFUNGRjI0ICSiwSMzYjN7kjMyATM0IicfJye35XX3JSSWFURiojIDJCLiIjVgMlS4J7bwVmUlZXa4NWQiojIOJyebpjIkJHUiwiI6MTOyUDMggDM4ATMyAjMiojI4J7QiwiIkV7bsNmLtVGdzl7ctY6ciojIz5GRiwiI5y114y11sy11Jy11qCq9Iy11iojIh94QiwiI6gzN7YDO4gDOyETM6UDNiojIklkIs4XZzxWYmpjIyNHZisnOiwmbBJye0ICRiwiI34zdGNTUzMTORp6ZEtSO8dVMXFnN8ZkRpdmUEFjSMBTc4YDR5ADSUdWZy2UYtVjYaBlZ8skdrcXOWFFa9QHRatyT9h6R0lXRRtmcahWbwBjSxAnVHRldWR4QrQjTK3mYFVlTshXZxJma';
        GC.ActiveReports.Core.PageReport.LicenseKey = '';
    </script>

    <style>
        .whole {
            position: relative;
        }
        .sidemenu-bar {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 10;
        }
        .right-side {
            /* position: absolute;
            top: 0;
            left: 0; */
            width: 100%;
            padding-left: 56px;
        }
        .header-bar {
            
        }
        .contents-common {
            padding-top: 10px;
        }
        @media (min-width: 1900px) {
            .contents-common {
                width: 1840px !important;
            }
        }
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
    </style>
</head>
<body>
    <div id="app">
        <div class="whole">
            <div class="sidemenu-bar">
            <sidemenu-component :has-auth="{{ session('hasAuth') }}"></sidemenu-component>
            </div>
            <div class="right-side">
                <div class="header-bar">
                <header-component :loginuser="{{ \Auth::user() }}"></header-component>
                </div>
                @if (session('flash_success'))
                    <div class="flash-message label-success">
                        <button type="button" class="btn-close-info" onClick="closeInfo(this);">×</button>
                        {{ session('flash_success') }}
                    </div>
                @endif
                @if (session('flash_error'))
                    <div class="flash-message label-danger">
                        <button type="button" class="btn-close-info" onClick="closeInfo(this);">×</button>
                        {{ session('flash_error') }}
                    </div>
                @endif
                <div class="contents-common container-fluid">
                    <h4>@yield('title')</h4>
                    @yield('content')
                </div>
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