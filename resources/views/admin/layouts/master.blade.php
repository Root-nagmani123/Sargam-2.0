<!DOCTYPE html>
<html lang="zxx">

<head>
    @include('admin.layouts.pre_header')
    <title>@yield('title')</title>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="{{asset('admin_assets/images/logos/favicon.ico')}}" alt="loader" class="lds-ripple img-fluid">
    </div>
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
</body>

</html>