<!DOCTYPE html>
<html lang="zxx">

<head>
    @include('admin.layouts.pre_header')
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    {{-- <link href="{{ asset('css/forms.css') }}" rel="stylesheet"> --}}
    {{-- @stack('styles') --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    @section('css')
    <style>
        .nav-item .tab-item .active {
    background-color:#bbd9f7;
            border-radius: 10px;
            color: #ffffff !important;
    transition: all 0.3s ease-in-out;
}
.mini-nav {
    display: flex;
    flex-direction: column;
}

.mini-nav-ul {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.mini-bottom {
    margin-top: auto !important;
}
/* Remove default Bootstrap dropdown arrow */
.dropdown-toggle-custom::after {
    display: none !important;
}

/* Custom arrow icon animation */
.dropdown-toggle-custom .dropdown-arrow {
    transition: transform 0.25s ease;
}

/* Rotate arrow when open */
.show > .dropdown-toggle-custom .dropdown-arrow {
    transform: rotate(180deg);
}
    .my-filled-icon {
        font-variation-settings: 'FILL' 1; /* Sets the fill to its maximum value (1) */
        color: blue; /* You can also change the color of the icon */
    }

    .my-unfilled-icon {
        font-variation-settings: 'FILL' 0; /* Sets the fill to its minimum value (0) */
    }
    </style>
    <style>
    .calendar {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .calendar th {
        background: #f8f9fa;
        padding: 8px;
        text-align: center;
        font-weight: 600;
    }
    .calendar td {
        width: 14.28%;
        height: 65px;
        padding: 6px;
        vertical-align: top;
        border: 1px solid #e5e5e5;
        text-align: right;
        position: relative;
    }
    .holiday {
        background-color: #ffe5e5 !important;
        border-left: 4px solid #dc3545 !important;
        font-weight: 600;
    }
    .holiday span {
        font-size: 11px;
        display: block;
        color: #dc3545;
        text-align: left;
        margin-top: 4px;
    }
</style>

</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="{{ asset('admin_assets/images/logos/favicon.ico') }}" alt="loader" class="lds-ripple img-fluid">
    </div>
    <div class="loading d-none" id="ajaxLoader">Loading&#8230;</div>
    <div id="main-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-wrapper">
            @include('admin.layouts.header')
            @include('admin.layouts.aside')
            <div class="body-wrapper">
                @yield('content')
            </div>
        </div>
    </div>
    
    @include('admin.layouts.footer')
    <script src="{{ asset('js/forms.js') }}"></script>
    @stack('scripts')
</body>

</html>
