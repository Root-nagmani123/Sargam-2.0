<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    @include('fc.layouts.pre_header')
<style>
    body { display: flex; flex-direction: column; min-height: 100vh; }
</style>
</head>

<body>
    @include('fc.layouts.header')
    @yield('content')
    @include('fc.layouts.footer')

</body>

</html>