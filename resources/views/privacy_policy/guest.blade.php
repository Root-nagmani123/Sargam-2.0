{{-- Light shell for visitors who are not signed in (the policy is linked from the login page).
     Signed-in users get admin.layouts.master instead - see privacy_policy/index. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Sargam 2.0 | Lal Bahadur Shastri National Academy of Administration</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('admin_assets/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ @filemtime(public_path('css/custom.css')) ?: time() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/sargam-app.css') }}?v={{ @filemtime(public_path('css/sargam-app.css')) ?: time() }}">
</head>
<body style="background: var(--ds-canvas);">
    <header class="bg-white border-bottom">
        <div class="container-fluid d-flex align-items-center justify-content-between py-2">
            <a href="{{ url('/') }}" class="d-inline-flex align-items-center" aria-label="Sargam 2.0 sign in">
                <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="Sargam 2.0" height="40">
            </a>
            <a href="{{ url('/') }}" class="btn btn-outline-primary btn-sm">Sign in</a>
        </div>
    </header>
    <main id="main-content" class="py-3">
        @yield('content')
    </main>
</body>
</html>
