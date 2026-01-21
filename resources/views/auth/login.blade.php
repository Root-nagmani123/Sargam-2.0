<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">

<head>
    <!-- Force light mode - prevent system theme detection -->
    <script>
        // CRITICAL: This must run BEFORE Bootstrap loads to prevent dark mode detection
        (function() {
            'use strict';
            
            // Set light theme immediately
            document.documentElement.setAttribute('data-bs-theme', 'light');
            
            // Override matchMedia to prevent Bootstrap from detecting dark mode preference
            if (window.matchMedia) {
                const originalMatchMedia = window.matchMedia.bind(window);
                window.matchMedia = function(query) {
                    const result = originalMatchMedia(query);
                    
                    // Intercept prefers-color-scheme queries
                    if (query && query.includes('prefers-color-scheme')) {
                        // Create a fake MediaQueryList that always returns false for dark mode
                        const fakeResult = {
                            matches: false,
                            media: query,
                            onchange: null,
                            addListener: function() {},
                            removeListener: function() {},
                            addEventListener: function() {},
                            removeEventListener: function() {},
                            dispatchEvent: function() { return false; }
                        };
                        
                        // If query is for dark mode, return false
                        if (query.includes('dark')) {
                            return fakeResult;
                        }
                    }
                    
                    return result;
                };
            }
            
            // Monitor and prevent theme changes on html element
            const htmlObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && 
                        mutation.attributeName === 'data-bs-theme') {
                        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                        if (currentTheme !== 'light') {
                            document.documentElement.setAttribute('data-bs-theme', 'light');
                            document.documentElement.style.colorScheme = 'light';
                        }
                    }
                });
            });
            
            // Start observing html element immediately
            htmlObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-bs-theme']
            });
            
            // Periodic check as fallback
            setInterval(function() {
                if (document.documentElement.getAttribute('data-bs-theme') !== 'light') {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                    document.documentElement.style.colorScheme = 'light';
                }
            }, 250);
        })();
    </script>
    
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="Login to Sargam - LBSNAA Portal. Secure access for students, faculty, and staff.">
    <meta name="keywords" content="LBSNAA, Sargam, Login, Government of India">
    <meta name="author" content="LBSNAA">
    <meta name="theme-color" content="#003d7a">
    <!-- Force light color scheme to prevent system dark mode -->
    <meta name="color-scheme" content="light">
    
    <meta property="og:title" content="Login - Sargam | LBSNAA">
    <meta property="og:description" content="Secure portal access for LBSNAA community">
    <meta property="og:type" content="website">
    
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <!-- Force light mode CSS - must load before Bootstrap -->
    <style id="force-light-mode-login">
    /* CRITICAL: Force light mode before Bootstrap CSS loads */
    html, html[data-bs-theme], html[data-bs-theme="dark"], html[data-bs-theme="light"] {
      color-scheme: light !important;
      --bs-body-bg: #fff !important;
      --bs-body-color: #212529 !important;
    }
    
    /* Override Bootstrap's dark mode media query */
    @media (prefers-color-scheme: dark) {
      html, html[data-bs-theme], html[data-bs-theme="dark"], html[data-bs-theme="light"],
      body, body[data-bs-theme], body[data-bs-theme="dark"], body[data-bs-theme="light"] {
        color-scheme: light !important;
        --bs-body-bg: #fff !important;
        --bs-body-color: #212529 !important;
        --bs-emphasis-color: #000 !important;
        --bs-secondary-color: rgba(33, 37, 41, 0.75) !important;
        --bs-secondary-bg: #e9ecef !important;
        --bs-tertiary-color: rgba(33, 37, 41, 0.5) !important;
        --bs-tertiary-bg: #f8f9fa !important;
        --bs-border-color: #dee2e6 !important;
        background-color: #fff !important;
        color: #212529 !important;
      }
    }
    </style>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- CRITICAL: Force light mode CSS - must load AFTER Bootstrap CSS -->
    <style id="force-light-mode-override-login">
    /* Override ALL Bootstrap dark mode styles - this MUST come after Bootstrap CSS */
    :root,
    [data-bs-theme="light"],
    [data-bs-theme="dark"],
    html,
    html[data-bs-theme],
    html[data-bs-theme="light"],
    html[data-bs-theme="dark"],
    body,
    body[data-bs-theme],
    body[data-bs-theme="light"],
    body[data-bs-theme="dark"] {
      color-scheme: light !important;
      --bs-body-bg: #fff !important;
      --bs-body-color: #212529 !important;
      --bs-emphasis-color: #000 !important;
      --bs-secondary-color: rgba(33, 37, 41, 0.75) !important;
      --bs-secondary-bg: #e9ecef !important;
      --bs-tertiary-color: rgba(33, 37, 41, 0.5) !important;
      --bs-tertiary-bg: #f8f9fa !important;
      --bs-border-color: #dee2e6 !important;
      --bs-border-color-translucent: rgba(0, 0, 0, 0.175) !important;
      --bs-link-color: #0d6efd !important;
      --bs-link-hover-color: #0a58ca !important;
      --bs-heading-color: inherit !important;
      --bs-body-color-rgb: 33, 37, 41 !important;
      --bs-body-bg-rgb: 255, 255, 255 !important;
      background-color: #fff !important;
      color: #212529 !important;
    }
    
    /* Force override Bootstrap's dark mode media query */
    @media (prefers-color-scheme: dark) {
      *,
      :root,
      html,
      html[data-bs-theme],
      html[data-bs-theme="light"],
      html[data-bs-theme="dark"],
      body,
      body[data-bs-theme],
      body[data-bs-theme="light"],
      body[data-bs-theme="dark"],
      .card,
      .modal,
      .dropdown-menu,
      .popover,
      .tooltip,
      .offcanvas,
      .navbar,
      .nav,
      .btn,
      .form-control,
      .form-select,
      .table,
      .alert,
      .badge,
      .list-group,
      .pagination {
        color-scheme: light !important;
        --bs-body-bg: #fff !important;
        --bs-body-color: #212529 !important;
        --bs-emphasis-color: #000 !important;
        --bs-secondary-color: rgba(33, 37, 41, 0.75) !important;
        --bs-secondary-bg: #e9ecef !important;
        --bs-tertiary-color: rgba(33, 37, 41, 0.5) !important;
        --bs-tertiary-bg: #f8f9fa !important;
        --bs-border-color: #dee2e6 !important;
        --bs-border-color-translucent: rgba(0, 0, 0, 0.175) !important;
        background-color: #fff !important;
        color: #212529 !important;
      }
    }
    </style>
    <link href="{{asset('admin_assets/css/accesibility-style_v1.css')}}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="{{asset('admin_assets/css/accesibility-style_v1.css')}}" rel="stylesheet">

    <title>Login - Sargam | LBSNAA</title>

    <style>
    /* ============================================
       MODERN FULLSCREEN LOGIN WITH BACKGROUND
       GIGW Compliant + WCAG 2.1 AAA
    ============================================ */

    :root {
        --primary-blue: #003d7a;
        --primary-blue-dark: #002952;
        --primary-blue-darker: #001a3d;
        --accent-orange: #ff6b35;
        --accent-saffron: #ff9933;
        --accent-green: #138808;
        --text-primary: #1a1a2e;
        --text-secondary: #4a5568;
        --text-muted: #6b7280;
        --success-color: #059669;
        --error-color: #dc2626;
        --border-color: #d1d5db;
        --border-light: #e5e7eb;
        --bg-white: #ffffff;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
        --shadow-xl: 0 25px 60px rgba(0,0,0,0.2);
        --shadow-focus: 0 0 0 3px rgba(0,61,122,0.25);
        --transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
        --transition-smooth: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        --radius-2xl: 1.5rem;
        --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    *, *::before, *::after {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html, body {
        height: 100%;
        overflow: hidden;
    }

    body {
        font-family: var(--font-sans);
        font-size: 1rem;
        line-height: 1.6;
        color: var(--text-primary);
        -webkit-font-smoothing: antialiased;
    }

    /* ===== Skip Link ===== */
    .skip-to-content {
        position: absolute;
        top: -100%;
        left: 1rem;
        background: var(--text-primary);
        color: white;
        padding: 0.75rem 1rem;
        text-decoration: none;
        border-radius: 0 0 var(--radius-md) var(--radius-md);
        z-index: 9999;
        font-weight: 600;
        font-size: 0.875rem;
        transition: top var(--transition-base);
    }

    .skip-to-content:focus {
        top: 0;
        outline: 3px solid var(--accent-orange);
        color: white;
    }

    /* Focus States - WCAG AAA */
    :focus-visible {
        outline: 3px solid var(--accent-orange);
        outline-offset: 3px;
    }

    :focus:not(:focus-visible) { outline: none; }

    /* ===== Main Layout ===== */
    .login-wrapper {
        height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    /* ===== Background Carousel ===== */
    .bg-carousel-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
    }

    .bg-carousel-container .carousel,
    .bg-carousel-container .carousel-inner,
    .bg-carousel-container .carousel-item {
        height: 100% !important;
    }

    .bg-carousel-container .carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: brightness(0.6);
        transition: transform 8s ease-out;
    }

    .bg-carousel-container .carousel-item.active img {
        transform: scale(1.05);
    }

    /* Dark Gradient Overlay */
    .bg-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            135deg,
            rgba(0, 41, 82, 0.85) 0%,
            rgba(0, 61, 122, 0.7) 50%,
            rgba(0, 26, 61, 0.85) 100%
        );
        z-index: 1;
    }

    /* Pattern Overlay */
    .bg-pattern {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        z-index: 2;
        pointer-events: none;
    }

    /* ===== Header - Redesigned ===== */
    .login-header {
        position: relative;
        z-index: 100;
    }

    /* Top Bar - Tricolor */
    .header-tricolor {
        height: 4px;
        background: linear-gradient(90deg, var(--accent-saffron) 33.33%, white 33.33%, white 66.66%, var(--accent-green) 66.66%);
    }

    /* Main Header Bar */
    .header-main {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(12px);
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        padding: 0.75rem 0;
    }

    .header-main .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    /* Left: GoI + LBSNAA */
    .header-left {
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .header-govt {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-right: 1.25rem;
        border-right: 1px solid var(--border-light);
    }

    .header-govt img {
        height: 32px;
        width: auto;
    }

    .header-govt span {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
    }

    .header-lbsnaa {
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .header-lbsnaa img {
        height: 42px;
        max-width: 200px;
        width: auto;
    }

    /* Right: Digital India + NeGD + Utils */
    .header-right {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .header-digital-india {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, rgba(0, 61, 122, 0.06) 0%, rgba(0, 41, 82, 0.04) 100%);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-light);
    }

    .header-digital-india img {
        height: 36px;
        width: auto;
    }

    .header-digital-india .negd-badge {
        font-size: 0.7rem;
        color: var(--primary-blue);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .header-utils {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .header-utils a {
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        color: var(--text-secondary);
        text-decoration: none;
        border-radius: var(--radius-md);
        transition: all var(--transition-base);
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .header-utils a:hover {
        background: var(--bg-subtle);
        color: var(--primary-blue);
    }

    /* ===== Main Content Area ===== */
    .main-content {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 10;
        padding: 1rem;
    }

    /* ===== Login Card - Glassmorphism ===== */
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: var(--radius-2xl);
        box-shadow: var(--shadow-xl), 0 0 80px rgba(0,0,0,0.3);
        width: 100%;
        max-width: 440px;
        padding: 2rem;
        position: relative;
        overflow: hidden;
        animation: cardFloat 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes cardFloat {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Tricolor Top Accent */
    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
        background: linear-gradient(90deg, 
            var(--accent-saffron) 0%, var(--accent-saffron) 33.33%, 
            white 33.33%, white 66.66%, 
            var(--accent-green) 66.66%, var(--accent-green) 100%);
    }

    /* Logo Section */
    .login-logo {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .login-logo img {
        max-width: 200px;
        height: auto;
    }

    .login-logo h1 {
        color: var(--primary-blue);
        font-size: 1.5rem;
        font-weight: 700;
        margin-top: 0.75rem;
        margin-bottom: 0.25rem;
    }

    .login-logo p {
        color: var(--text-muted);
        font-size: 0.875rem;
        margin: 0;
    }

    /* ===== Form Styles ===== */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.9375rem;
    }

    .form-label i {
        color: var(--primary-blue);
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-lg);
        font-size: 1rem;
        color: var(--text-primary);
        background: var(--bg-white);
        transition: all var(--transition-base);
    }

    .form-control:hover:not(:focus) {
        border-color: var(--text-muted);
    }

    .form-control:focus {
        border-color: var(--primary-blue);
        box-shadow: var(--shadow-focus);
        outline: none;
    }

    .form-control::placeholder {
        color: #9ca3af;
    }

    /* Input Group */
    .input-group {
        display: flex;
    }

    .input-group .form-control {
        border-right: none;
        border-radius: var(--radius-lg) 0 0 var(--radius-lg);
    }

    .input-group .form-control:focus + .input-addon {
        border-color: var(--primary-blue);
    }

    .input-addon {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 1rem;
        background: #f8fafc;
        border: 2px solid var(--border-color);
        border-left: none;
        border-radius: 0 var(--radius-lg) var(--radius-lg) 0;
        cursor: pointer;
        color: var(--text-muted);
        transition: all var(--transition-base);
        min-width: 50px;
    }

    .input-addon:hover {
        background: white;
        color: var(--primary-blue);
    }

    .form-text {
        font-size: 0.8125rem;
        color: var(--text-muted);
        margin-top: 0.375rem;
    }

    /* Checkbox */
    .form-check {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .form-check-input {
        width: 18px;
        height: 18px;
    }

    .form-check-label {
        font-size: 13px;
    }

    /* Mobile - Skip link */
    .skip-to-content {
        font-size: 13px;
        padding: 10px 14px;
    }

    /* Mobile - Logo image in card */
    .login-card-enhanced img[src*="logo.svg"] {
        width: 100%;
        max-width: 280px;
        margin-bottom: 16px;
    }

    /* Mobile - Word of the day section */
    .login-card-enhanced h5 {
        font-size: 13px;
    }

    .login-card-enhanced hr + div p {
        font-size: 13px;
    }

    /* ===== SMALL MOBILE (480px and up) ===== */
    @media (min-width: 480px) {
        .login-card-enhanced {
            padding: 24px 20px;
        }

        .login-card-enhanced h2 {
            font-size: 24px;
        }

        .login-card-enhanced p {
            font-size: 14px;
        }

        .form-label {
            font-size: 14px;
        }

        .form-control {
            font-size: 14px;
            padding: 12px 15px;
        }

        .login-button-enhanced {
            padding: 14px;
            font-size: 16px;
        }

        .main-header-nav .navbar-brand img {
            max-width: 240px;
        }

        .brand-link img {
            max-width: 110px;
        }

        .login-card-enhanced img[src*="logo.svg"] {
            max-width: 320px;
        }
    }

    /* ===== TABLET (768px and up) ===== */
    @media (min-width: 768px) {
        .login-card-enhanced {
            padding: 28px 24px;
            max-height: 90vh;
        }

        .login-card-enhanced h2 {
            font-size: 26px;
            margin-bottom: 10px;
        }

        .login-card-enhanced p {
            font-size: 15px;
            margin-bottom: 24px;
        }

        .form-label {
            font-size: 14px;
            margin-bottom: 7px;
        }

        .form-control {
            font-size: 15px;
            padding: 13px 16px;
        }

        .login-button-enhanced {
            padding: 15px;
            font-size: 16px;
        }

        .main-header-nav {
            padding: 16px 0;
        }

        .main-header-nav .container-fluid {
            flex-direction: row;
            justify-content: space-between !important;
            align-items: center !important;
            gap: 0;
        }

        .main-header-nav .navbar-brand {
            justify-content: flex-start;
            width: auto;
        }

        .main-header-nav .navbar-brand .lh-sm {
            text-align: left;
        }

        .top-header {
            display: block;
        }

        .gigw-header-top {
            display: block !important;
        }

        .main-header-nav .navbar-brand img {
            max-width: 280px;
        }

        .brand-link img {
            max-width: 120px;
        }

        .login-page-wrapper {
            padding: 14px;
        }

        .gigw-footer .d-flex {
            flex-direction: row;
            text-align: left;
        }

        .gigw-footer {
            padding: 18px 0;
        }

        .top-header span,
        .gigw-footer span {
            font-size: 12px;
        }

        .password-toggle-btn,
        .input-group-text {
            padding: 0 14px;
            font-size: 20px;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
        }

        .form-check-label {
            font-size: 14px;
        }

        .security-badge {
            font-size: 13px;
            padding: 11px 14px;
        }

        .login-card-enhanced img[src*="logo.svg"] {
            max-width: 380px;
        }

        .main-header-nav .container-fluid > a:first-child,
        .main-header-nav .container-fluid > div:last-child {
            width: auto;
        }
    }

    /* ===== DESKTOP (992px and up) ===== */
    @media (min-width: 992px) {
        .login-card-enhanced {
            padding: 34px 28px;
            border-radius: 16px;
            max-height: 92vh;
            max-width: 480px;
        }

        .login-page-wrapper {
            padding: 16px;
        }

        .login-card-enhanced h2 {
            font-size: 30px;
            text-align: center;
            margin-bottom: 12px;
        }

        .login-card-enhanced p {
            font-size: 15px;
            text-align: center;
            margin-bottom: 26px;
        }

        .form-label {
            font-size: 14px;
            text-align: left;
            margin-bottom: 8px;
        }

        .form-control {
            font-size: 15px;
            padding: 14px 18px;
        }

        .login-button-enhanced {
            padding: 16px;
            font-size: 17px;
        }

        .main-header-nav {
            padding: 18px 0;
        }

        .main-header-nav .container-fluid {
            flex-direction: row;
            justify-content: space-between !important;
            align-items: center !important;
            gap: 0;
        }

        .main-header-nav .navbar-brand {
            justify-content: flex-start;
            width: auto;
        }

        .main-header-nav .navbar-brand .lh-sm {
            text-align: left;
        }

        .main-header-nav .navbar-brand img {
            max-width: 320px;
        }

        .brand-link img {
            max-width: 140px;
        }

        .top-header span {
            font-size: 13px;
        }

        .gigw-footer {
            padding: 20px 0;
        }

        .gigw-footer span {
            font-size: 13px;
        }

        /* Desktop: Show carousel on lg screens */
        .col-lg-8 {
            display: block !important;
        }

        .col-lg-4 {
            width: auto !important;
        }

        .password-toggle-btn,
        .input-group-text {
            padding: 0 16px;
            font-size: 20px;
        }

        .form-check-input {
            width: 22px;
            height: 22px;
        }

        .form-check-label {
            font-size: 14px;
        }

        .security-badge {
            font-size: 13px;
            padding: 12px 16px;
        }

        .login-card-enhanced img[src*="logo.svg"] {
            max-width: 420px;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 48px;
            height: 48px;
        }
    }

    /* ===== LARGE DESKTOP (1200px and up) ===== */
    @media (min-width: 1200px) {
        .login-card-enhanced {
            max-width: 520px;
            width: 100%;
            padding: 36px 32px;
        }

        .main-header-nav .navbar-brand img {
            max-width: 340px;
        }

        .brand-link img {
            max-width: 150px;
        }

        .login-card-enhanced h2 {
            font-size: 32px;
        }

        .login-card-enhanced p {
            font-size: 16px;
        }

        .form-label {
            font-size: 15px;
        }

        .form-control {
            font-size: 16px;
            padding: 15px 18px;
        }

        .login-button-enhanced {
            padding: 17px;
            font-size: 17px;
        }

        .top-header span {
            font-size: 14px;
        }

        .gigw-footer span {
            font-size: 14px;
        }

        .login-card-enhanced img[src*="logo.svg"] {
            max-width: 480px;
        }
    }

    /* ===== EXTRA LARGE (1400px and up) ===== */
    @media (min-width: 1400px) {
        .login-card-enhanced {
            max-width: 560px;
            padding: 40px;
        }

        .container {
            max-width: 1320px;
        }

        .main-header-nav .navbar-brand img {
            max-width: 360px;
        }

        .brand-link img {
            max-width: 160px;
        }

        .login-card-enhanced h2 {
            font-size: 34px;
        }

        .login-card-enhanced img[src*="logo.svg"] {
            max-width: 520px;
        }
    }

    /* ===== Tablet Header Navigation Collapse ===== */
    @media (max-width: 991.98px) {
        .main-header-nav .navbar-collapse {
            text-align: center;
            border-top: 1px solid var(--border-color);
            margin-top: 12px;
            padding-top: 12px;
        }
    }

    /* ===== Ultra-Wide Monitors (1920px and up) ===== */
    @media (min-width: 1920px) {
        .login-card-enhanced {
            max-width: 600px;
            padding: 44px;
        }

        .login-card-enhanced h2 {
            font-size: 36px;
        }

        .login-card-enhanced img[src*="logo.svg"] {
            max-width: 550px;
        }
    }

    /* ===== Landscape Orientation for Mobile/Tablet ===== */
    @media (max-height: 600px) and (orientation: landscape) {
        .login-card-enhanced {
            padding: 16px 20px;
            max-height: 90vh;
        }

        .login-card-enhanced h2 {
            font-size: 20px;
            margin-bottom: 6px;
        }

        .login-card-enhanced p {
            font-size: 12px;
            margin-bottom: 12px;
        }

        .form-label {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .form-control {
            padding: 10px 12px;
            font-size: 13px;
        }

        .login-button-enhanced {
            padding: 11px;
            font-size: 14px;
        }

        .login-card-enhanced img[src*="logo.svg"] {
            max-width: 200px;
            margin-bottom: 8px;
        }

        .security-badge {
            padding: 8px 10px;
            font-size: 11px;
            margin-top: 12px;
        }

        .mb-3 {
            margin-bottom: 0.75rem !important;
        }

        .mb-4 {
            margin-bottom: 1rem !important;
        }

        .main-header-nav {
            padding: 10px 0;
        }

        .gigw-footer {
            padding: 12px 0;
        }

        hr {
            margin: 0.75rem 0;
        }

        .login-card-enhanced h5 {
            font-size: 12px;
            margin-top: 0.5rem !important;
        }
    }

    /* ===== Print Styles ===== */
    @media print {
        .main-header-nav,
        .gigw-footer,
        .top-header,
        .carousel,
        .skip-to-content {
            display: none !important;
        }

        .login-card-enhanced {
            box-shadow: none;
            border: 2px solid #000;
            page-break-inside: avoid;
        }

        body {
            background: white;
        }
    }

    /* Additional Modern Enhancements */
    
    /* Smooth Page Load Animation */
    @keyframes fadeInPage {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    body {
        animation: fadeInPage 0.4s ease-out;
    }

    /* Input Placeholder Animation */
    .form-control::placeholder {
        transition: var(--transition-smooth);
    }

    .form-control:focus::placeholder {
        opacity: 0.6;
        transform: translateX(4px);
    }

    /* Label Highlight on Focus */
    .form-control:focus ~ .form-label,
    .form-control:not(:placeholder-shown) ~ .form-label {
        color: var(--primary-blue);
    }

    /* Enhanced Card Shadow on Scroll */
    .login-card-enhanced {
        transform: translateY(0);
    }

    /* Improved Link Underline Effect */
    .gigw-footer a {
        position: relative;
        padding-bottom: 2px;
    }

    .gigw-footer a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 1px;
        background: var(--accent-orange);
        transition: width 0.3s ease;
    }

    .gigw-footer a:hover::after {
        width: 100%;
    }

    /* Loading Spinner for Button */
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .spinner-border {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        vertical-align: text-bottom;
        border: 0.2em solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spin 0.75s linear infinite;
    }

    /* Micro-interaction: Scale on Click */
    .btn:active,
    .form-check-input:active {
        transform: scale(0.97);
    }

    /* Enhanced Skip Link Accessibility */
    .skip-to-content:focus {
        top: 0;
        outline: 4px solid var(--accent-orange);
        outline-offset: 3px;
        z-index: 10000;
        font-weight: 600;
    }

    /* Logo Hover Effect */
    .navbar-brand img,
    .brand-link img {
        transition: var(--transition-smooth);
    }

    .navbar-brand:hover img,
    .brand-link:hover img {
        transform: scale(1.02);
        filter: brightness(1.05);
    }

    /* Enhanced Form Validation Visual Feedback */
    .form-control.is-valid {
        border-color: var(--success-color);
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.5rem) center;
        background-size: calc(0.75em + 1rem) calc(0.75em + 1rem);
        padding-right: calc(1.5em + 1.5rem);
    }

    .form-control.is-invalid {
        border-color: var(--error-color);
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.5rem) center;
        background-size: calc(0.75em + 1rem) calc(0.75em + 1rem);
        padding-right: calc(1.5em + 1.5rem);
    }

    /* Pulse Animation for Security Badge */
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    .security-badge i {
        animation: pulse 2s ease-in-out infinite;
    }

    /* Enhanced Dropdown Styling */
    .language-dropdown .dropdown-menu {
        border-radius: 8px;
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-lg);
        padding: 8px 0;
        margin-top: 8px;
    }

    .language-dropdown .dropdown-item {
        padding: 10px 20px;
        transition: var(--transition-smooth);
        border-radius: 4px;
        margin: 0 8px;
    }

    .language-dropdown .dropdown-item:hover {
        background: var(--primary-blue-light);
        color: var(--primary-blue);
    }

    /* Modern Scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f3f5;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-blue);
        border-radius: 5px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-blue-dark);
    }

    /* Enhanced Focus Indicator - WCAG 2.1 AAA Compliant */
    *:focus-visible {
        outline: 3px solid var(--accent-orange);
        outline-offset: 3px;
    }

    /* Reduce Motion for Accessibility */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }

    /* High Contrast Mode Support */
    @media (prefers-contrast: high) {
        .login-card-enhanced {
            border: 3px solid var(--primary-blue);
        }

        .form-control {
            border-width: 3px;
        }

        .login-button-enhanced {
            border: 3px solid white;
        }

        a,
        .btn {
            text-decoration: underline;
        }
    }

    /* Dark Mode Preparation (DISABLED - Force light mode only) */
    @media (prefers-color-scheme: dark) {
        /* All dark mode styles disabled - force light mode */
        html, body, :root {
            color-scheme: light !important;
            --bs-body-bg: #fff !important;
            --bs-body-color: #212529 !important;
            background-color: #fff !important;
            color: #212529 !important;
        }
        :root {
            --text-primary: #1f1f1f !important;
            --text-secondary: #4a5568 !important;
            --border-color: #cbd5e0 !important;
            --bg-light: #f7fafc !important;
            --bg-white: #ffffff !important;
        }

        body {
            background: linear-gradient(135deg, #ffffff 0%, #f7fafc 50%, #ffffff 100%) !important;
        }

        .login-card-enhanced {
            background: #ffffff !important;
            border-color: #cbd5e0 !important;
        }

        .form-control {
            background: #ffffff !important;
            color: #212529 !important;
            border-color: #cbd5e0 !important;
        }
    }

    /* ===== Touch-Friendly Enhancements ===== */
    @media (pointer: coarse) {
        /* Larger touch targets for mobile */
        .btn,
        .form-control,
        a,
        .form-check-input {
            min-height: 44px;
        }

        .password-toggle-btn,
        .input-group-text {
            min-width: 44px;
        }

        .form-check-input {
            min-width: 24px;
            min-height: 24px;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 56px;
            height: 56px;
        }
    }

    /* ===== Enhanced Hover Effects (Desktop Only) ===== */
    @media (hover: hover) {
        .form-control:hover:not(:focus) {
            border-color: var(--text-secondary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-check-input:hover:not(:checked) {
            border-color: var(--primary-blue);
        }

        .login-card-enhanced:hover {
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.16);
        }
    }

    /* ===== No-Hover Devices (Remove Hover States) ===== */
    @media (hover: none) {
        .header-nav-link::after,
        .forgot-password-link::after,
        .gigw-footer a::after {
            display: none;
        }

        .login-card-enhanced:hover {
            transform: none;
        }
    }

    /* ===== Custom Utility Classes ===== */
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .visually-hidden-focusable:not(:focus):not(:focus-within) {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    /* ===== Loading State ===== */
    .btn-loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }

    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.6s linear infinite;
    }

    /* ===== Tooltip Enhancement ===== */
    [data-tooltip] {
        position: relative;
        cursor: help;
    }

    [data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%) translateY(-8px);
        padding: 6px 12px;
        background: var(--primary-blue-darker);
        color: white;
        font-size: 12px;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s, transform 0.3s;
        z-index: 1000;
    }

    [data-tooltip]:hover::after,
    [data-tooltip]:focus::after {
        opacity: 1;
        transform: translateX(-50%) translateY(-4px);
    }

    /* ===== Improved Focus Ring ===== */
    .focus-ring-primary:focus-visible {
        outline: 3px solid var(--primary-blue);
        outline-offset: 2px;
    }

    .focus-ring-orange:focus-visible {
        outline: 3px solid var(--accent-orange);
        outline-offset: 2px;
    }

    /* ===== Error/Success Messages ===== */
    .alert-modern {
        border-radius: 8px;
        border: none;
        padding: 14px 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInDown 0.4s ease-out;
    }

    .alert-modern.alert-error {
        background: rgba(196, 30, 58, 0.1);
        color: var(--error-color);
        border-left: 4px solid var(--error-color);
    }

    .alert-modern.alert-success {
        background: rgba(19, 136, 8, 0.1);
        color: var(--success-color);
        border-left: 4px solid var(--success-color);
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ===== Skeleton Loading (Future Use) ===== */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s ease-in-out infinite;
        border-radius: 4px;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    /* ===== Ripple Effect ===== */
    .ripple {
        position: relative;
        overflow: hidden;
    }

    .ripple::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .ripple:active::after {
        width: 300px;
        height: 300px;
    }

    /* ===== Improved Input States ===== */
    .form-control:disabled,
    .form-control[readonly] {
        background-color: #e9ecef;
        opacity: 0.7;
        cursor: not-allowed;
    }

    .form-control:user-invalid {
        border-color: var(--error-color);
    }

    .form-control:user-valid {
        border-color: var(--success-color);
    }
    </style>
</head>

<body>
    <a href="#login-form" class="skip-to-content">Skip to Main Content</a>

    <div class="login-wrapper">
        <!-- Background Carousel -->
        <div class="bg-carousel-container" aria-hidden="true">
            <div id="bgCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="false">
                <div class="carousel-inner">
                    @for($i = 1; $i <= 10; $i++)
                    <div class="carousel-item {{ $i === 1 ? 'active' : '' }}">
                        <img src="{{ asset('images/carasoul/' . $i . '.webp') }}"
                            alt=""
                            loading="{{ $i <= 2 ? 'eager' : 'lazy' }}">
                    </div>
                    @endfor
                </div>
            </div>
            <div class="bg-overlay"></div>
            <div class="bg-pattern"></div>
        </div>

        <!-- Floating Decorative Shapes -->
        <div class="floating-shapes" aria-hidden="true">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>

        <!-- Header -->
        <header class="login-header" role="banner">
            <div class="header-tricolor"></div>
            <div class="header-main">
                <div class="container">
                    <div class="header-left">
                        <div class="header-govt">
                            <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                                alt="National Flag of India" loading="eager">
                            <span>भारत सरकार | Government of India</span>
                        </div>
                        <a href="{{ url('/') }}" class="header-lbsnaa d-none d-lg-block" aria-label="LBSNAA Home">
                            <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png"
                                alt="LBSNAA - Lal Bahadur Shastri National Academy of Administration" loading="eager">
                        </a>
                    </div>
                    <div class="header-right d-none d-lg-block">
                        <nav class="header-utils" aria-label="Utility Navigation">
                            <a href="#login-form"><i class="bi bi-arrow-down-circle" aria-hidden="true"></i> Skip to Content</a>
                            <a href="#" id="accessibilityTrigger" role="button"><i class="bi bi-universal-access" aria-hidden="true"></i> Accessibility</a>
                        </nav>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content" role="main">
            <div class="login-card">
                <!-- Logo & Title -->
                <div class="login-logo">
                    <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" 
                        alt="Sargam - LBSNAA Portal"
                        loading="eager">
                    <h1 id="login-form">Welcome Back</h1>
                    <p>Sign in to access your LBSNAA account</p>
                </div>

                <!-- Error Alert -->
                @if(session('error'))
                <div class="alert-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if($errors->any())
                <div class="alert-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <div>
                        @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Login Form -->
                <form action="{{ route('post_login') }}" method="POST" id="loginForm" novalidate>
                    @csrf

                    <!-- Username -->
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="bi bi-person-fill" aria-hidden="true"></i>
                            Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                            class="form-control @error('username') is-invalid @enderror" 
                            id="username"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Enter your username"
                            autocomplete="username"
                            required
                            autofocus>
                        <small class="form-text">Your official registration number or ID</small>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="password" class="form-label mb-0">
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                Password <span class="text-danger">*</span>
                            </label>
                        </div>
                        <div class="input-group">
                            <input type="password" 
                                class="form-control @error('password') is-invalid @enderror" 
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>
                            <button type="button" class="input-addon" id="togglePassword" aria-label="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                            
                        </div>
                        <div class="text-end">
                        
                        <a href="{{ route('password.request') ?? '#' }}" class="forgot-link">
                                Forgot Password?
                            </a>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        Sign In
                    </button>

                    <!-- Security -->
                    <div class="security-badge">
                        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                        <span>Secure & Encrypted Connection</span>
                    </div>
                </form>

                            <hr class="my-3">

                            <div class="text-center">
                                <h5 class="text-muted mt-3 mb-2" style="font-size: 14px;">
                                    आज का शब्द / Word of the Day
                                </h5>
                                <p class="mb-0" style="font-size: 14px; font-weight: 500;">
                                    अधिग्रहण-मोचन - De-requisition
                                </p>
                            </div>
                        </div>
                    </main>
                </div>
                <div class="col-lg-8 d-none d-lg-block" role="complementary" aria-label="Campus images carousel">
                    <div id="carouselExampleFade" 
                        class="carousel slide carousel-fade" 
                        data-bs-ride="carousel" 
                        data-bs-interval="5000" 
                        data-bs-pause="hover" 
                        data-bs-touch="true"
                        data-bs-keyboard="true" 
                        data-bs-wrap="true" 
                        aria-label="LBSNAA Campus Carousel"
                        aria-roledescription="carousel">
                        
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="1" aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="3" aria-label="Slide 4"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="4" aria-label="Slide 5"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="5" aria-label="Slide 6"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="6" aria-label="Slide 7"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="7" aria-label="Slide 8"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="8" aria-label="Slide 9"></button>
                            <button type="button" data-bs-target="#carouselExampleFade" data-bs-slide-to="9" aria-label="Slide 10"></button>
                        </div>

                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="{{ asset('images/carasoul/1.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 1"
                                    loading="eager">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/2.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 2"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/3.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 3"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/4.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 4"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/5.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 5"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/6.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 6"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/7.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 7"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/8.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 8"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/9.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 9"
                                    loading="lazy">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/10.webp') }}"
                                    class="d-block w-100 img-fluid carasoul-image" 
                                    alt="LBSNAA Campus view 10"
                                    loading="lazy">
                            </div>
                        </div>

                        <button class="carousel-control-prev" 
                            type="button" 
                            data-bs-target="#carouselExampleFade"
                            data-bs-slide="prev"
                            aria-label="Previous slide">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" 
                            type="button" 
                            data-bs-target="#carouselExampleFade"
                            data-bs-slide="next"
                            aria-label="Next slide">
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
                        icon.textContent = isPassword ? 'visibility_off' : 'visibility';
                        icon.style.transform = 'scale(1)';
                    }, 150);
                    
                    const newLabel = isPassword ? 'Hide password' : 'Show password';
                    this.setAttribute('aria-label', newLabel);
                    this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                    passwordInput.focus();
                });

                // Keyboard support
                toggleButton.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            }
        })();

        // Form Validation Enhancement
        (function() {
            const form = document.querySelector('form[action*="post_login"]');
            if (!form) return;

            const username = document.getElementById('usernameInput');
            const password = document.getElementById('passwordInput');

            // Real-time validation feedback
            function validateField(field) {
                const isValid = field.value.trim().length > 0;
                
                if (field.value.length > 0) {
                    if (isValid) {
                        field.classList.add('is-valid');
                        field.classList.remove('is-invalid');
                        field.setAttribute('aria-invalid', 'false');
                    } else {
                        field.classList.add('is-invalid');
                        field.classList.remove('is-valid');
                        field.setAttribute('aria-invalid', 'true');
                    }
                } else {
                    field.classList.remove('is-valid', 'is-invalid');
                }
                
                return isValid;
            }

            [username, password].forEach(field => {
                if (!field) return;
                
                field.addEventListener('input', function() {
                    validateField(this);
                });

                field.addEventListener('blur', function() {
                    if (!this.value.trim() && this.hasAttribute('required')) {
                        this.classList.add('is-invalid');
                        this.setAttribute('aria-invalid', 'true');
                    }
                });

                field.addEventListener('focus', function() {
                    // Announce to screen readers
                    const label = this.labels[0];
                    if (label) {
                        this.setAttribute('aria-describedby', label.id || label.textContent);
                    }
                });
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Clear previous errors
                [username, password].forEach(field => {
                    if (field) {
                        field.classList.remove('is-invalid', 'is-valid');
                    }
                });

                // Validation
                if (!username || !username.value.trim()) {
                    if (username) {
                        username.classList.add('is-invalid');
                        username.setAttribute('aria-invalid', 'true');
                        username.focus();
                    }
                    isValid = false;
                } else if (username) {
                    username.classList.add('is-valid');
                    username.setAttribute('aria-invalid', 'false');
                }

                if (!password || !password.value) {
                    if (password) {
                        password.classList.add('is-invalid');
                        password.setAttribute('aria-invalid', 'true');
                        if (isValid) password.focus();
                    }
                    isValid = false;
                } else if (password) {
                    password.classList.add('is-valid');
                    password.setAttribute('aria-invalid', 'false');
                }

                if (!isValid) {
                    e.preventDefault();
                    
                    // Announce error to screen readers
                    const errorMsg = document.createElement('div');
                    errorMsg.setAttribute('role', 'alert');
                    errorMsg.setAttribute('aria-live', 'assertive');
                    errorMsg.className = 'visually-hidden';
                    errorMsg.textContent = 'Please fill in all required fields';
                    document.body.appendChild(errorMsg);
                    setTimeout(() => errorMsg.remove(), 3000);
                    
                    return false;
                }

                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-loading');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.setAttribute('data-original-text', originalText);
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...';
                }

                // Set fresh login flag
                sessionStorage.setItem('fresh_login', 'true');
            });
        })();

        // Keyboard Navigation Enhancement
        (function() {
            const form = document.querySelector('form[action*="post_login"]');
            if (!form) return;

            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        e.preventDefault();
                        submitBtn.click();
                    }
                }
            });
        })();

        // Accessibility: Focus Management
        (function() {
            const inputs = document.querySelectorAll('.form-control, .form-check-input, .btn');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    this.classList.remove('focused');
                });
            });

            // Trap focus in login card on mobile
            const loginCard = document.querySelector('.login-card-enhanced');
            if (loginCard && window.innerWidth < 768) {
                const focusableElements = loginCard.querySelectorAll(
                    'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                const firstFocusable = focusableElements[0];
                const lastFocusable = focusableElements[focusableElements.length - 1];

                loginCard.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            if (document.activeElement === firstFocusable) {
                                e.preventDefault();
                                lastFocusable.focus();
                            }
                        } else {
                            if (document.activeElement === lastFocusable) {
                                e.preventDefault();
                                firstFocusable.focus();
                            }
                        }
                    }
                });
            }
        })();

        // Ripple Effect for Buttons
        (function() {
            const rippleButtons = document.querySelectorAll('.ripple');
            rippleButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple-effect');

                    this.appendChild(ripple);

                    setTimeout(() => ripple.remove(), 600);
                });
            });

            // Add ripple effect styles
            const style = document.createElement('style');
            style.textContent = `
                .ripple-effect {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple-animation 0.6s ease-out;
                    pointer-events: none;
                }
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        })();

        // Accessibility Widget Trigger
        (function() {
            const accessibilityTrigger = document.getElementById('uw-widget-custom-trigger');
            if (accessibilityTrigger) {
                accessibilityTrigger.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            }
        })();

        // Auto-save form state (for better UX)
        (function() {
            const username = document.getElementById('usernameInput');
            const rememberMe = document.getElementById('keepLoggedIn');

            // Load saved username if remember me was checked
            if (username && localStorage.getItem('rememberUsername') === 'true') {
                const savedUsername = localStorage.getItem('savedUsername');
                if (savedUsername) {
                    username.value = savedUsername;
                }
            }

            // Save username on change
            if (username && rememberMe) {
                rememberMe.addEventListener('change', function() {
                    if (this.checked && username.value) {
                        localStorage.setItem('savedUsername', username.value);
                        localStorage.setItem('rememberUsername', 'true');
                    } else {
                        localStorage.removeItem('savedUsername');
                        localStorage.removeItem('rememberUsername');
                    }
                });

                username.addEventListener('input', function() {
                    if (rememberMe.checked) {
                        localStorage.setItem('savedUsername', this.value);
                    }
                });
            }
        })();

        // Performance: Lazy load carousel images
        (function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                            observer.unobserve(img);
                        }
                    });
                });

                const lazyImages = document.querySelectorAll('img[loading="lazy"]');
                lazyImages.forEach(img => imageObserver.observe(img));
            }
        })();

        // Announce page load to screen readers
        (function() {
            window.addEventListener('load', function() {
                const announcement = document.createElement('div');
                announcement.setAttribute('role', 'status');
                announcement.setAttribute('aria-live', 'polite');
                announcement.className = 'visually-hidden';
                announcement.textContent = 'Login page loaded successfully. Please enter your credentials.';
                document.body.appendChild(announcement);
                setTimeout(() => announcement.remove(), 3000);
            });
        })();
        
        // Final safeguard: Force light mode on page load
        window.addEventListener('load', function() {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            document.documentElement.style.colorScheme = 'light';
            document.documentElement.style.setProperty('--bs-body-bg', '#fff', 'important');
            document.documentElement.style.setProperty('--bs-body-color', '#212529', 'important');
            
            // Remove any dark mode classes
            document.documentElement.classList.remove('dark');
            if (document.body) {
                document.body.classList.remove('dark');
                document.body.style.colorScheme = 'light';
            }
        });
        </script>

        <footer class="gigw-footer mt-auto" role="contentinfo">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <span class="text-center text-md-start">
                        &copy; <?php echo date('Y'); ?> LBSNAA Mussoorie, Govt of India. All Rights Reserved
                        <span class="d-none d-md-inline">|</span>
                        <span class="d-block d-md-inline mt-1 mt-md-0">
                            Support: <a href="mailto:support.lbsnaa@nic.in" class="text-white text-decoration-none">support.lbsnaa@nic.in</a> 
                            <span class="d-none d-sm-inline">| Ph: 1014 (EPABX)</span>
                        </span>
                    </span>
                    <div class="text-center text-md-end">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="bi bi-people-fill me-1" aria-hidden="true"></i>
                            Active Users: <strong>135</strong>
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
    
    <!-- Immediately intercept Bootstrap's theme detection on login page -->
    <script>
        (function() {
            'use strict';
            // Force light mode immediately after Bootstrap loads
            document.documentElement.setAttribute('data-bs-theme', 'light');
            document.documentElement.style.colorScheme = 'light';
            
            // Override Bootstrap's getTheme function if it exists
            if (window.bootstrap) {
                window.bootstrap.getTheme = function() {
                    return 'light';
                };
            }
            
            // Force light mode on window load
            window.addEventListener('load', function() {
                document.documentElement.setAttribute('data-bs-theme', 'light');
                document.documentElement.style.colorScheme = 'light';
                document.documentElement.style.setProperty('--bs-body-bg', '#fff', 'important');
                document.documentElement.style.setProperty('--bs-body-color', '#212529', 'important');
                
                // Remove any dark mode classes
                document.documentElement.classList.remove('dark');
                if (document.body) {
                    document.body.classList.remove('dark');
                    document.body.style.colorScheme = 'light';
                }
            });
            
            // Periodic check as fallback
            setInterval(function() {
                if (document.documentElement.getAttribute('data-bs-theme') !== 'light') {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                    document.documentElement.style.colorScheme = 'light';
                }
            }, 500);
        })();
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