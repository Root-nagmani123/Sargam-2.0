<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Guest TA Claim') – Sargam 2.0</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="gtc-body">
<div class="gtc-gov d-flex align-items-center justify-content-between px-4">
    <span><span class="gtc-flag me-2" aria-hidden="true"></span>भारत सरकार | Government of India</span>
    <span class="d-none d-md-flex gap-3 opacity-75"><a href="#gtc-content" class="text-white text-decoration-none">Skip to content</a><span>English</span></span>
</div>
<header class="gtc-header d-flex align-items-center flex-wrap gap-3 px-4 py-2">
    <a class="gtc-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
        <svg width="34" height="28" viewBox="0 0 34 28" aria-hidden="true"><path d="M9 2 16 14 9 26 2 14z" fill="#c62828"/><path d="M25 2 32 14 25 26 18 14z" fill="#1f4fb8"/><path d="M17 8 21 14 17 20 13 14z" fill="#f2a900"/></svg>
        Sargam 2.0
    </a>
    <nav class="nav gtc-topnav mx-auto d-none d-lg-flex" aria-label="Main">
        <a class="nav-link" href="{{ url('/') }}">Home</a>
        <a class="nav-link active" href="{{ route('guest-ta-claim.dashboard') }}">Setup</a>
        <a class="nav-link" href="#">Communications</a>
        <a class="nav-link" href="#">Academics</a>
    </nav>
    @auth
        @php($gtcUserName = auth()->user()->{config('guest-ta-claim.user_name_column', 'name')})
        <div class="d-flex align-items-center gap-2 ms-auto ms-lg-0">
            <span class="gtc-avatar">{{ \Modules\GuestTaClaim\Support\Format::initials($gtcUserName) }}</span>
            <span class="lh-sm"><b class="d-block">{{ $gtcUserName }}</b><small class="text-secondary">Guest TA Claim</small></span>
        </div>
    @endauth
</header>
<div class="gtc-layout">
    <nav class="gtc-rail" aria-label="Modules">
        <a href="#"><i class="bi bi-grid-3x3-gap"></i>Training Setup</a>
        <a href="#"><i class="bi bi-calendar3"></i>Time Table</a>
        <a href="#"><i class="bi bi-people"></i>HR Management</a>
        <a href="#"><i class="bi bi-diagram-3"></i>Mappings</a>
        <a href="#"><i class="bi bi-file-earmark-text"></i>FC Forms</a>
        <a href="{{ route('guest-ta-claim.dashboard') }}" class="active"><i class="bi bi-receipt"></i>Guest TA Claim</a>
        <a href="#"><i class="bi bi-journal-bookmark"></i>Course Repository</a>
    </nav>
    @yield('content')
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
