<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light">
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    <!-- Bootstrap 5.3.x (latest v5) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://bootstrapdemos.adminmart.com/matdash/dist/assets/css/styles.css">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/ico" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <!-- jQuery Steps -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.js"></script>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Admin UI enhancements (Bootstrap 5 + LBSNAA) -->
    <link href="{{ asset('css/admin-ui-enhancements.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link href="https://cdn.ux4g.gov.in/UX4G@2.0.8/css/ux4g-min.css" rel="stylesheet">
    <!-- LBSNAA theme overrides for this layout -->
    <style>
        :root {
            --lbsnaa-primary: #004a93;
            --lbsnaa-primary-rgb: 0, 74, 147;
            --lbsnaa-primary-dark: #003d7a;
            --lbsnaa-primary-subtle: rgba(0, 74, 147, 0.08);
            --bs-primary: var(--lbsnaa-primary);
            --bs-primary-rgb: var(--lbsnaa-primary-rgb);
        }
        .timetable-layout .govt-header-top { background: linear-gradient(135deg, var(--lbsnaa-primary) 0%, var(--lbsnaa-primary-dark) 100%) !important; }
        .timetable-layout .header { border-bottom: 3px solid var(--lbsnaa-primary); box-shadow: 0 4px 12px rgba(var(--lbsnaa-primary-rgb), 0.12); }
        .timetable-layout .header-logo-ashoka, .timetable-layout .header-logo-lbsnaa { transition: transform 0.2s ease; }
        .timetable-layout .header-logo-ashoka:hover, .timetable-layout .header-logo-lbsnaa:hover { transform: scale(1.02); }
        .timetable-layout main { background: linear-gradient(180deg, #f8fafc 0%, #ffffff 120px); }
        .timetable-layout footer { background: linear-gradient(135deg, var(--lbsnaa-primary) 0%, var(--lbsnaa-primary-dark) 100%) !important; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08); }
        .timetable-layout .footer-brand { font-weight: 600; letter-spacing: 0.02em; }
    </style>
    @stack('styles')
</head>
<x-session_message />

<body class="min-vh-100 d-flex flex-column bg-white timetable-layout">
    <!-- Skip to main content (GIGW) -->
    <a href="#main-content" class="visually-hidden-focusable btn btn-primary position-fixed top-0 start-0 m-2 m-lg-3 rounded-3 z-3" style="z-index: 1030;">Skip to main content</a>

    <!-- Government Header Bar (LBSNAA theme) -->
    <div class="govt-header d-none d-lg-block" role="banner" aria-label="Government of India identity bar">
        <div class="govt-header-top text-white py-2">
            <div class="container-xxl">
                <div class="row align-items-center g-2">
                    <div class="col">
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/330px-Flag_of_India.svg.png"
                                 alt="National Flag of India"
                                 height="22"
                                 class="d-inline-block object-fit-contain rounded-1">
                            <span class="fw-semibold small text-uppercase opacity-95">भारत सरकार | Government of India</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            <a href="#main-content" class="text-white text-decoration-none small opacity-90">Skip to main content</a>
                            <span class="text-white opacity-50 d-none d-md-inline">|</span>
                            <div class="d-flex align-items-center gap-1" aria-label="Font size controls">
                                <button class="btn btn-sm btn-link text-white p-0 px-1 opacity-90" type="button" aria-label="Increase font size">A+</button>
                                <button class="btn btn-sm btn-link text-white p-0 px-1 opacity-90" type="button" aria-label="Normal font size">A</button>
                                <button class="btn btn-sm btn-link text-white p-0 px-1 opacity-90" type="button" aria-label="Decrease font size">A-</button>
                            </div>
                            <span class="text-white opacity-50 d-none d-md-inline">|</span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-white dropdown-toggle p-0 opacity-90"
                                        type="button"
                                        id="languageDropdown"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    English
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0 mt-2" aria-labelledby="languageDropdown">
                                    <li><a class="dropdown-item rounded-2" href="#">English</a></li>
                                    <li><a class="dropdown-item rounded-2" href="#">हिन्दी</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Header (LBSNAA branding) -->
    <header class="header sticky-top bg-white mb-0" role="banner">
        <div class="container-xxl py-2 py-lg-3">
            <nav class="navbar navbar-expand-lg navbar-light p-0" aria-label="Primary navigation">
                <div class="d-flex flex-nowrap align-items-center gap-2 gap-lg-3">
                    <a class="navbar-brand d-flex align-items-center text-decoration-none" href="{{ url('/') }}" aria-label="Home">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg"
                             alt="Ashoka Emblem" class="header-logo-ashoka" height="72" width="auto">
                    </a>
                    <span class="vr opacity-25 flex-shrink-0 d-none d-md-block" style="height: 48px;"></span>
                    <a class="navbar-brand d-flex align-items-center text-decoration-none" href="{{ url('/') }}" aria-label="LBSNAA">
                        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" class="header-logo-lbsnaa" height="72" width="auto">
                    </a>
                </div>
                <button class="navbar-toggler border-2 border-primary rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav gap-2">
                        <li class="nav-item"><a class="nav-link text-primary fw-medium rounded-2 px-3" href="{{ url('/') }}"><i class="bi bi-house-door me-1"></i>Home</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="flex-grow-1 py-4 py-lg-5" role="main" tabindex="-1">
        <div class="container-xxl px-3 px-md-4">
            @yield('content')
        </div>
    </main>

    <!-- Footer (LBSNAA theme) -->
    <footer class="mt-auto text-white py-4" role="contentinfo">
        <div class="container-xxl px-3 px-md-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-8">
                    <p class="mb-0 small footer-brand opacity-95">
                        &copy; {{ date('Y') }} Lal Bahadur Shastri National Academy of Administration, Mussoorie, Uttarakhand
                    </p>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <p class="mb-0 small opacity-75">Sargam 2.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    @stack('scripts')
</body>

</html>
