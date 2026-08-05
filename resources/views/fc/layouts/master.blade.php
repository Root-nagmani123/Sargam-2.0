<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    {{-- Point link-preview crawlers (RCS/Google Messages, WhatsApp, email clients) at a
         1x1 transparent placeholder so they don't fall back to scraping the page's own
         <img> tags — on some FC pages that would be the coordinator's signature. --}}
    <meta property="og:image" content="{{ asset('images/blank.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:image" content="{{ asset('images/blank.png') }}">
    @include('fc.layouts.pre_header')
    @stack('styles')
</head>

<body>
    @include('fc.layouts.header')
    @yield('content')
    @include('fc.layouts.footer')
    @stack('scripts')

</body>

</html>
