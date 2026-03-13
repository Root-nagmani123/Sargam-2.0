<!doctype html>
<html lang="en">

<head class="h-100">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    @include('fc.layouts.pre_header')

<body class="d-flex flex-column min-vh-100 bg-light">
    @include('fc.layouts.header')

    <div id="content" class="flex-grow-1 d-flex flex-column">
        @yield('content')
    </div>

    @include('fc.layouts.footer')
    @stack('scripts')

</body>

</html>
