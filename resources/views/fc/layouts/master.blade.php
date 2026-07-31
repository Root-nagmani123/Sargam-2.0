<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    @include('fc.layouts.pre_header')
    @stack('styles')
</head>

<body class="fc-portal">
    @include('fc.layouts.header')
    {{-- Single skip-link target for every FC page (GIGW / WCAG 2.4.1). It is a
         flex column that grows, so views whose top-level element carries
         `flex: 1` keep the behaviour they had as direct children of <body>. --}}
    <main id="content" tabindex="-1" style="display:flex; flex-direction:column; flex:1;">
        @yield('content')
    </main>
    @include('fc.layouts.footer')
    @stack('scripts')

</body>

</html>
