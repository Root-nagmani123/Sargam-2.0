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
    .tab-item.active h6 {
    font-weight: bold;
    color: #af2910;
    }
    .tab-item.active {
        border-bottom: 4px solid #af2910;
        background-color:rgb(210, 230, 250);
        padding: 5px;
        border-radius: 10px;
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
