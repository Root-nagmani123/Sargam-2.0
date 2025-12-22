<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{asset('admin_assets/css/accesibility-style_v1.css')}}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Login - LBSNAA</title>

    <style>
    /* GIGW Color Palette Focus (High Contrast) */
    :root {
        --primary-blue: #004a93;
        /* Used for main branding and primary action */
        --primary-blue-light: #e0eafc;
        /* Light background pattern */
        --primary-blue-dark: #003366;
        --text-primary: #212529;
        --text-secondary: #6c757d;
        --accent-orange: #ff6b35;
        /* High-contrast focus/accessibility */
        --border-color: #dee2e6;
        --success-color: #28a745;
        --error-color: #dc3545;
        --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.6;
        color: var(--text-primary);
        background: linear-gradient(135deg, #f5f7fb 0%, #ffffff 100%);
        min-height: calc(100% - 56px);
        display: flex;
        flex-direction: column;
    }

    /* GIGW - Skip to Content & Focus Styles */
    .skip-to-content {
        position: absolute;
        top: -50px;
        left: 0;
        background: #000;
        color: white;
        padding: 12px 16px;
        text-decoration: none;
        border-radius: 0 0 4px 0;
        z-index: 9999;
        transition: top 0.3s;
        font-weight: 500;
        font-size: 14px;
    }

    .skip-to-content:focus {
        top: 0;
        outline: 3px solid var(--accent-orange);
        outline-offset: 2px;
    }

    /* Enhanced Focus States - GIGW Compliant */
    a:focus,
    button:focus,
    input:focus,
    .form-check-input:focus,
    .dropdown-toggle:focus {
        outline: 3px solid var(--accent-orange) !important;
        outline-offset: 2px !important;
        box-shadow: 0 0 0 1px var(--primary-blue) !important;
    }

    /* GIGW Top Header Bar (Blue) - Enhanced */
    .gigw-header-top {
        background: linear-gradient(90deg, var(--primary-blue) 0%, #003d7a 100%);
        color: white;
        padding: 6px 0;
        font-size: 13px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .gigw-header-top a {
        color: white;
        text-decoration: none;
        padding: 6px 10px;
        margin-inline-end: 6px;
        transition: var(--transition-smooth);
        border-radius: 3px;
    }

    .gigw-header-top a:hover,
    .gigw-header-top a:focus {
        background-color: rgba(255, 255, 255, 0.25);
    }

    /* Main Header (Logo Bar) - Modern Design */
    .main-header-nav {
        background: white;
        border-bottom: 1px solid var(--border-color);
        padding: 12px 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        backdrop-filter: blur(10px);
        background-color: rgba(255, 255, 255, 0.98);
    }

    .logo-text {
        color: #333;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.3;
    }

    .logo-text small {
        display: block;
        font-size: 12px;
        font-weight: 400;
        color: var(--text-secondary);
    }

    .header-nav-link {
        color: var(--text-primary) !important;
        font-size: 15px;
        font-weight: 500;
        transition: var(--transition-smooth);
        position: relative;
    }

    .header-nav-link:hover {
        color: var(--primary-blue) !important;
    }

    .header-nav-link::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--primary-blue);
        transition: width 0.3s ease;
    }

    .header-nav-link:hover::after {
        width: 100%;
    }

    /* GIGW Font Size Adjusters - Enhanced */
    .font-size-adjuster .btn {
        font-weight: 700;
        font-size: 14px;
        padding: 6px 10px;
        border: 1.5px solid var(--border-color);
        color: var(--text-primary);
        background: white;
        transition: var(--transition-smooth);
    }

    .font-size-adjuster .btn:hover {
        background-color: #f0f0f0;
        border-color: var(--primary-blue);
    }

    .font-size-adjuster .btn:active {
        background: var(--primary-blue);
        color: white;
    }

    .login-btn-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
        color: white;
        font-weight: 600;
        padding: 10px 24px;
        border-radius: 6px;
        text-decoration: none;
        transition: var(--transition-smooth);
        display: inline-block;
        box-shadow: 0 2px 8px rgba(0, 74, 147, 0.2);
    }

    .login-btn-header:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 74, 147, 0.3);
        color: white;
    }

    .login-btn-header:active {
        transform: translateY(0);
    }

    /* Main Content & Background Pattern */
    .login-page-wrapper {
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    /* Login Card - Modern Glassmorphism & Enhanced Design */
    .login-card-image {
        background: white;
        border: 1px solid rgba(0, 74, 147, 0.1);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        max-width: 480px;
        width: 100%;
        padding: 40px;
        text-align: center;
        transition: var(--transition-smooth);
        position: relative;
        overflow: hidden;
    }

    .login-card-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue) 0%, var(--accent-orange) 100%);
    }

    .login-card-image:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .login-card-image h2 {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 28px;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .login-card-image p {
        color: var(--text-secondary);
        font-size: 15px;
        margin-bottom: 28px;
        line-height: 1.5;
    }

    /* Form Controls - Modern Styling */
    .form-label {
        display: block;
        text-align: left;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
        transition: color 0.2s;
    }

    .form-label i {
        color: var(--primary-blue);
        margin-right: 6px;
    }

    .form-control {
        padding: 12px 16px;
        border-radius: 8px;
        border: 1.5px solid var(--border-color);
        font-size: 15px;
        transition: var(--transition-smooth);
        background-color: #f8f9fa;
    }

    .form-control:focus {
        background-color: white;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1) !important;
    }

    .form-control::placeholder {
        color: #adb5bd;
        font-size: 14px;
    }

    .input-group .form-control {
        border-right: none;
    }

    .password-toggle-btn {
        background-color: #f8f9fa;
        border: 1.5px solid var(--border-color);
        border-left: none;
        border-radius: 0 8px 8px 0;
        color: var(--text-secondary);
        padding: 0 14px;
        cursor: pointer;
        transition: var(--transition-smooth);
        font-size: 18px;
    }

    .password-toggle-btn:hover {
        background-color: white;
        color: var(--primary-blue);
    }

    .password-toggle-btn:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1);
    }

    /* Login Button - Modern with Gradient & Animation */
    .login-button {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
        border-color: transparent;
        border: none;
        color: white;
        font-weight: 700;
        padding: 12px 0;
        transition: var(--transition-smooth);
        margin-top: 24px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(0, 74, 147, 0.2);
        position: relative;
        overflow: hidden;
    }

    .login-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .login-button:hover::before {
        left: 100%;
    }

    .login-button:hover {
        background: linear-gradient(135deg, var(--primary-blue-dark) 0%, #002a5a 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 74, 147, 0.3);
    }

    .login-button:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(0, 74, 147, 0.2);
    }

    .login-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* GIGW Footer - Enhanced */
    .gigw-footer {
        background: linear-gradient(90deg, var(--primary-blue) 0%, #003d7a 100%);
        color: #e0e0e0;
        padding: 16px 0;
        font-size: 13px;
        text-align: center;
        margin-top: auto;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
    }

    .gigw-footer a {
        color: #fff;
        text-decoration: none;
        margin-left: 12px;
        transition: var(--transition-smooth);
        font-weight: 500;
    }

    .gigw-footer a:hover,
    .gigw-footer a:focus {
        color: var(--accent-orange);
        text-decoration: underline;
    }

    .gigw-footer span {
        display: inline-block;
        line-height: 1.6;
    }

    /* Language Dropdown Adjustment - Enhanced */
    .language-dropdown .btn {
        color: var(--text-primary);
        border: 1.5px solid var(--border-color);
        background: white;
        transition: var(--transition-smooth);
        border-radius: 6px;
        font-weight: 500;
    }

    .language-dropdown .btn:hover {
        border-color: var(--primary-blue);
        background: #f8f9fa;
    }

    .language-dropdown .btn:focus {
        border-color: var(--primary-blue) !important;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1) !important;
    }

    /* Additional Modern Form Enhancements */
    .form-check-input {
        width: 20px;
        height: 20px;
        margin-top: 2px;
        border: 1.5px solid var(--border-color);
        border-radius: 4px;
        cursor: pointer;
        transition: var(--transition-smooth);
    }

    .form-check-input:checked {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
    }

    .form-check-input:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.1) !important;
    }

    .form-check-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .form-check-label i {
        margin-right: 6px;
        color: var(--primary-blue);
        font-size: 14px;
    }

    .form-text {
        font-size: 13px;
        margin-top: 6px;
        color: var(--text-secondary);
    }

    /* Security Badge */
    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        color: var(--success-color);
        font-weight: 500;
        font-size: 13px;
        margin-top: 16px;
        padding: 8px 12px;
        background: rgba(40, 167, 69, 0.08);
        border-radius: 6px;
    }

    .security-badge i {
        font-size: 16px;
    }

    @media (max-width: 991.98px) {
        .main-header-nav .navbar-collapse {
            text-align: center;
            border-top: 1px solid var(--border-color);
            margin-top: 12px;
            padding-top: 12px;
        }

        .login-card-enhanced {
            padding: 24px 20px;
        }
    }

    /* --- Modern Enhanced Login Card ---*/
    .login-card-enhanced {
        border-radius: 12px;
        width: 100%;
        padding: 40px;
        text-align: left;
        position: relative;
        overflow: hidden;
        background: white;
        border: 1px solid rgba(0, 74, 147, 0.1);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        animation: slideInUp 0.5s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-card-enhanced::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue) 0%, var(--accent-orange) 100%);
    }

    .login-card-enhanced h2 {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 28px;
        margin-bottom: 6px;
        text-align: center;
        letter-spacing: -0.5px;
    }

    .login-card-enhanced p {
        color: #6c757d;
        font-size: 15px;
        margin-bottom: 28px;
        text-align: center;
        line-height: 1.5;
    }

    /* Form Control Focus Styles - GIGW Compliant */
    .form-control:focus,
    .btn:focus,
    .form-check-input:focus,
    a:focus {
        border-color: var(--primary-blue) !important;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.15) !important;
        outline: none;
    }

    /* Input Group Text */
    .input-group-text {
        background-color: #f8f9fa;
        border-left: none;
        cursor: pointer;
        color: var(--text-secondary);
        border-radius: 0 8px 8px 0;
        transition: var(--transition-smooth);
        border: 1.5px solid var(--border-color);
        border-left: none;
    }

    .input-group-text:hover {
        background-color: white;
        color: var(--primary-blue);
    }

    /* Enhanced Button Styles */
    .login-button-enhanced {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
        border: none;
        font-weight: 700;
        padding: 12px;
        border-radius: 8px;
        transition: var(--transition-smooth);
        color: white;
        font-size: 16px;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(0, 74, 147, 0.2);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .login-button-enhanced::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .login-button-enhanced:hover::before {
        left: 100%;
    }

    .login-button-enhanced:hover {
        background: linear-gradient(135deg, var(--primary-blue-dark) 0%, #002a5a 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 74, 147, 0.3);
    }

    .login-button-enhanced:active {
        transform: translateY(0);
    }

    /* Forgot Password Link */
    .forgot-password-link {
        color: var(--primary-blue) !important;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: var(--transition-smooth);
    }

    .forgot-password-link:hover,
    .forgot-password-link:focus {
        color: var(--primary-blue-dark) !important;
        text-decoration: underline;
    }

    /* Carousel Enhancements */
    #carouselExampleFade,
    #carouselExampleFade .carousel-inner,
    #carouselExampleFade .carousel-item {
        height: calc(100% - 56px) !important;
    }

    #carouselExampleFade .carousel-item img {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }

    .carasoul-image {
        object-fit: cover;
        width: 100%;
        height: 100vh !important;
        animation: zoomIn 0.5s ease-out;
    }

    @keyframes zoomIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Carousel Controls - GIGW Accessible */
    .carousel-control-prev,
    .carousel-control-next {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 50%;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-smooth);
    }

    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        background: rgba(0, 0, 0, 0.5);
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        filter: brightness(1.2);
    }

    /* Responsive Design - Tablet & Mobile */
    @media (max-width: 991.98px) {
        .login-page-wrapper {
            padding: 16px;
        }

        .main-header-nav .navbar-brand {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 575.98px) {
        .login-card-enhanced {
            padding: 24px 16px;
            border-radius: 8px;
        }

        .login-card-enhanced h2 {
            font-size: 24px;
        }

        .login-card-enhanced p {
            font-size: 14px;
        }

        .form-label {
            font-size: 13px;
        }

        .form-control {
            font-size: 14px;
            padding: 10px 12px;
        }

        .login-button-enhanced {
            padding: 10px;
            font-size: 15px;
        }

        /* Center Government of India + Ashoka emblem + LBSNAA text on small screens */
        .main-header-nav .container-fluid {
            flex-direction: column;
            justify-content: center !important;
            align-items: center !important;
            gap: 8px;
        }

        .main-header-nav .navbar-brand {
            justify-content: center;
        }

        .main-header-nav .navbar-brand .lh-sm {
            text-align: center;
        }

        .top-header span,
        .gigw-footer span {
            font-size: 12px;
        }
    }
    </style>
</head>

<body>
    <a href="#login-form-start" class="skip-to-content">Skip to Main Content</a>

    <div id="main-wrapper" class="d-flex flex-column min-vh-100">

        <div class="top-header d-flex justify-content-between align-items-center d-none d-md-block"
            style="background-color: #004a93; color: #fff; padding: 5px 0;">
            <div class="container">
                <div class="row">
                    <div class="col-md-3 d-flex align-items-center">
                        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                            alt="GoI Logo" height="30">
                        <span class="ms-2" style="font-size: 14px;">Government of India</span>
                    </div>
                    <div class="col-md-9 text-end d-flex justify-content-end align-items-center">
                        <ul class="nav justify-content-end align-items-center">
                            <li class="nav-item"><a href="#content" class="text-white text-decoration-none"
                                    style=" font-size: 12px;">Skip to Main Content</a></li>
                            <span class="text-muted me-3 ">|</span>
                            <li class="nav-item"><a class="text-white text-decoration-none"
                                    id="uw-widget-custom-trigger" contenteditable="false" style="cursor: pointer;"><img
                                        src="{{ asset('images/accessible.png') }}" alt="" width="20">
                                    <span class="text-white ms-1" style=" font-size: 12px;">
                                        More
                                    </span>
                                </a>

                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-header-nav sticky-top bg-white border-bottom shadow-sm">
            <div class="container">
                <nav class="navbar navbar-expand-lg py-2" role="navigation" aria-label="Primary Navigation">
                    <div class="container-fluid px-0 d-flex justify-content-between align-items-center">

                        <!-- Left: India Emblem + Text -->
                        <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="#"
                            aria-label="Government of India Home">

                            <img src="https://www.shutterstock.com/image-vector/indian-national-emblem-ashokas-lion-600nw-2534959015.jpg"
                                alt="State Emblem of India" width="60" class="img-fluid">

                            <div class="d-flex flex-column lh-sm">
                                <span class="fw-semibold text-dark" style="font-size: 1.1rem;">
                                    Government of India
                                </span>
                                <small class="text-muted" style="font-size: 0.9rem;">
                                    Lal Bahadur Shastri National Academy of Administration
                                </small>
                            </div>
                        </a>

                        <!-- Right: LBSNAA Logo -->
                        <div class="d-flex justify-content-end align-items-center">
                            <a href="{{ route('login') }}"
                                class="brand-link d-flex align-items-center gap-3 text-decoration-none"
                                aria-label="Login to LBSNAA Portal">

                                <!-- Light Mode Logo -->
                                <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png"
                                    alt="Lal Bahadur Shastri National Academy of Administration"
                                    class="brand-logo img-fluid d-none d-lg-block d-dark-none" width="230"
                                    height="auto">

                                <!-- Dark Mode Logo -->
                                <img src="https://upload.wikimedia.org/wikipedia/en/thumb/9/95/Digital_India_logo.svg/1200px-Digital_India_logo.svg.png"
                                    alt="LBSNAA Portal – Dark Mode"
                                    class="brand-logo img-fluid d-none d-lg-block d-dark-block" width="150"
                                    height="auto">

                                <!-- Mobile Fallback Logo -->
                                <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA"
                                    class="brand-logo img-fluid d-lg-none" width="160" height="auto">
                            </a>
                        </div>


                    </div>
                </nav>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row g-0 min-vh-100">
                <div class="col-lg-4 col-12 d-flex align-items-center justify-content-center bg-light">
                    <main class="login-page-wrapper flex-grow-1">
                        <div class="login-card-enhanced">
                            <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="LBSNAA Logo - Dark Mode"
                                class="img-fluid" style="width: 550px;">
                            <h2 id="login-form-start" tabindex="-1">Welcome Back</h2>
                            <p>Sign in to your account for application and status services.</p>
                            <form action="{{route('post_login')}}" method="POST" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="usernameInput" class="form-label">
                                        <i class="fas fa-user"></i>Username <span class="required-indicator"
                                            aria-hidden="true">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="usernameInput"
                                        placeholder="Enter your registered username" name="username"
                                        autocomplete="username" required aria-required="true"
                                        aria-describedby="usernameHelp">
                                    <small id="usernameHelp" class="form-text text-muted">Use your official registration
                                        number or
                                        ID.</small>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label for="passwordInput" class="form-label">
                                            <i class="fas fa-lock"></i>Password <span class="required-indicator"
                                                aria-hidden="true">*</span>
                                        </label>
                                        <a class="forgot-password-link" href="#" aria-label="Forgot Password link">
                                            Forgot Password?
                                        </a>
                                    </div>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="passwordInput"
                                            placeholder="Enter your password" name="password" required
                                            aria-required="true" autocomplete="current-password">
                                        <button type="button" class="btn input-group-text" id="togglePassword"
                                            aria-label="Toggle password visibility">
                                            <i class="material-icons menu-icon" aria-hidden="true">visibility</i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-start mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="keepLoggedIn"
                                            checked>
                                        <label class="form-check-label text-muted" for="keepLoggedIn">
                                            <i class="fas fa-history me-1"></i>Keep me logged in
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 login-button-enhanced"
                                    aria-label="Sign In to your account">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>Your connection is secure and encrypted.
                                    </small>
                                </div>
                            </form>
                            <hr class="my-2">
                            <div class="text-center">
                                <h5 class="text-muted mt-4">आज का शब्द / अब का शब्द उपलब्ध नहीं है</h5>
                                <p>अधिग्रहण-मोचन - De-requisition</p>
                            </div>
                        </div>
                    </main>
                </div>
                <div class="col-lg-8 d-none d-lg-block">
                    <div id="carouselExampleFade" class="carousel slide carousel-fade carousel-dark"
                        data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover" data-bs-touch="true"
                        data-bs-keyboard="true" data-bs-wrap="true" aria-roledescription="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="{{ asset('images/carasoul/1.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/2.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/3.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item" data-bs-interval="40000">
                                <img src="{{ asset('images/carasoul/4.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/5.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/6.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/7.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/8.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/9.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/10.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleFade"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleFade"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>



        <script>
        // ===== Modern Enhanced UX Scripts =====
        
        // Password Visibility Toggle with Smooth Interaction
        (function() {
            const toggleButton = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('passwordInput');

            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    const icon = this.querySelector('i');
                    
                    // Smooth icon animation
                    icon.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        icon.classList.toggle('fa-eye', !isPassword);
                        icon.classList.toggle('fa-eye-slash', isPassword);
                        icon.style.transform = 'scale(1)';
                    }, 150);
                    
                    this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                    passwordInput.focus();
                });
            }
        })();

        // Form Validation Enhancement
        (function() {
            const form = document.querySelector('form[action*="post_login"]');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                const username = document.getElementById('usernameInput');
                const password = document.getElementById('passwordInput');
                let isValid = true;

                // Clear previous errors
                [username, password].forEach(field => {
                    field.classList.remove('is-invalid');
                    field.classList.remove('is-valid');
                });

                // Validation
                if (!username.value.trim()) {
                    username.classList.add('is-invalid');
                    username.focus();
                    isValid = false;
                } else {
                    username.classList.add('is-valid');
                }

                if (!password.value) {
                    password.classList.add('is-invalid');
                    if (isValid) password.focus();
                    isValid = false;
                } else {
                    password.classList.add('is-valid');
                }

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                // Set fresh login flag
                sessionStorage.setItem('fresh_login', 'true');
            });

            // Real-time validation feedback
            const username = document.getElementById('usernameInput');
            const password = document.getElementById('passwordInput');

            [username, password].forEach(field => {
                field.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.add('is-valid');
                        this.classList.remove('is-invalid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.remove('is-invalid');
                    }
                });

                field.addEventListener('blur', function() {
                    if (!this.value.trim() && this.hasAttribute('required')) {
                        this.classList.add('is-invalid');
                    }
                });
            });
        })();

        // Login Button Loading State
        (function() {
            const loginBtn = document.querySelector('.login-button-enhanced');
            const form = document.querySelector('form[action*="post_login"]');

            if (loginBtn && form) {
                form.addEventListener('submit', function() {
                    loginBtn.disabled = true;
                    loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...';
                });
            }
        })();

        // Keyboard Navigation Enhancement (Tab through form)
        (function() {
            const form = document.querySelector('form[action*="post_login"]');
            if (!form) return;

            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target !== document.querySelector('.login-button-enhanced')) {
                    // Allow default form submission on Enter
                    if (e.target.tagName !== 'TEXTAREA') {
                        e.preventDefault();
                        form.submit();
                    }
                }
            });
        })();

        // Accessibility: Announce focus state
        (function() {
            const inputs = document.querySelectorAll('.form-control, .form-check-input, .btn');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    // Visual feedback is provided by CSS, this ensures screen readers know
                    this.setAttribute('aria-focus', 'true');
                });
                input.addEventListener('blur', function() {
                    this.removeAttribute('aria-focus');
                });
            });
        })();

        // Add CSS for form validation states if not present
        (function() {
            const styles = `
                .form-control.is-valid {
                    border-color: #28a745;
                    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
                }
                .form-control.is-invalid {
                    border-color: #dc3545;
                    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
                }
                .spinner-border-sm {
                    width: 1rem;
                    height: 1rem;
                    border-width: 0.2em;
                }
            `;
            const style = document.createElement('style');
            style.textContent = styles;
            document.head.appendChild(style);
        })();
        </script>

        <footer class="gigw-footer mt-auto">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <span class="mb-1 mb-md-0">
                        &copy; <?php echo date('Y'); ?> LBSNAA Mussoorie,Govt of India. All Right Reserved [ Support :
                        support[DOT]lbsnaa[AT]nic[DOT]in OR 1014(EPABX) ]
                    </span>
                    <div>
                        <span>Current Logged in user(s): 135.</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>

    <script>
    // Ensure Bootstrap is present; if CDN fails, load local fallback and then init carousel
    (function() {
        function initCarousel() {
            var el = document.getElementById('carouselExampleFade');
            if (!el || !(window.bootstrap && bootstrap.Carousel)) return;
            try {
                var carousel = bootstrap.Carousel.getOrCreateInstance(el, {
                    interval: 5000,
                    ride: 'carousel',
                    pause: 'hover',
                    touch: true,
                    keyboard: true,
                    wrap: true
                });
                // Lazy-load images except first
                var imgs = el.querySelectorAll('.carousel-item img');
                imgs.forEach(function(img, idx) {
                    if (idx > 0) img.setAttribute('loading', 'lazy');
                    img.setAttribute('decoding', 'async');
                });
            } catch (e) {
                /* swallow */
            }
        }

        function ensureBootstrap(cb) {
            if (window.bootstrap && bootstrap.Carousel) {
                cb();
                return;
            }
            var s = document.createElement('script');
            s.src = "{{ asset('admin_assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}";
            s.async = true;
            s.onload = cb;
            document.head.appendChild(s);
        }

        document.addEventListener('DOMContentLoaded', function() {
            ensureBootstrap(initCarousel);
        });
    })();
    </script>
</body>

</html>