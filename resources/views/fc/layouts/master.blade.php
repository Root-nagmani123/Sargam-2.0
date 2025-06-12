<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    @include('fc.layouts.pre_header')

</head>

<body>
<!-- Preloader -->
    <div class="preloader">
        <img src="{{ asset('admin_assets/images/logos/favicon.ico') }}" alt="loader" class="lds-ripple img-fluid">
    </div>
    @include('fc.layouts.header')
    @yield('content')
    @include('fc.layouts.footer')

</body>

</html>