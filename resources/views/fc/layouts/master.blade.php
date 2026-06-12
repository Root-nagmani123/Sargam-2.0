<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') {{ config('app.title_suffix') }}</title>
    @include('fc.layouts.pre_header')

<body>
    @include('fc.layouts.header')
    @yield('content')
    @include('fc.layouts.footer')
    @stack('scripts')

</body>

</html>
