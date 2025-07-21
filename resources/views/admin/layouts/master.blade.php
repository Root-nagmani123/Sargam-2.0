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
