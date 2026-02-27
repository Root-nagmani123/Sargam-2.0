<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">

<head>
    <!-- Force light mode -->
    <script>
        (function() {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            if (window.matchMedia) {
                const orig = window.matchMedia.bind(window);
                window.matchMedia = function(q) {
                    if (q && q.includes('prefers-color-scheme') && q.includes('dark')) {
                        return { matches: false, media: q, onchange: null, addListener: ()=>{}, removeListener: ()=>{}, addEventListener: ()=>{}, removeEventListener: ()=>{}, dispatchEvent: ()=>false };
                    }
                    return orig(q);
                };
            }
        })();
    </script>
    
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="Login to Sargam - LBSNAA Portal. Secure access for students, faculty, and staff.">
    <meta name="keywords" content="LBSNAA, Sargam, Login, Government of India">
    <meta name="author" content="LBSNAA">
    <meta name="theme-color" content="#003d7a">
    <meta name="color-scheme" content="light">
    
    <meta property="og:title" content="Login - Sargam | LBSNAA">
    <meta property="og:description" content="Secure portal access for LBSNAA community">
    <meta property="og:type" content="website">
    
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    
    <!-- Bootstrap 5.3.6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
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
        border: 2px solid var(--border-color);
        border-radius: 4px;
        cursor: pointer;
        appearance: none;
        background: white;
        transition: all var(--transition-base);
        flex-shrink: 0;
    }

    .form-check-input:checked {
        background: var(--primary-blue);
        border-color: var(--primary-blue);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-width='3' stroke-linecap='round' stroke-linejoin='round' d='M6 10l3 3 6-6'/%3e%3c/svg%3e");
        background-size: 12px;
        background-position: center;
        background-repeat: no-repeat;
    }

    .form-check-input:focus {
        box-shadow: var(--shadow-focus);
    }

    .form-check-label {
        font-size: 0.9375rem;
        color: var(--text-secondary);
        cursor: pointer;
    }

    /* ===== Submit Button ===== */
    .btn-login {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all var(--transition-base);
        box-shadow: 0 4px 15px rgba(0, 61, 122, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        position: relative;
        overflow: hidden;
    }

    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 61, 122, 0.4);
    }

    .btn-login:hover::before {
        left: 100%;
    }

    .btn-login:active {
        transform: translateY(0);
    }

    .btn-login:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .btn-login.loading {
        color: transparent;
        pointer-events: none;
    }

    .btn-login.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border: 2px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* Forgot Password */
    .forgot-link {
        color: var(--primary-blue);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all var(--transition-base);
    }

    .forgot-link:hover {
        color: var(--primary-blue-dark);
        text-decoration: underline;
    }

    /* Security Badge */
    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
        padding: 0.625rem 1rem;
        background: rgba(5, 150, 105, 0.1);
        border-radius: var(--radius-lg);
        color: var(--success-color);
        font-size: 0.8125rem;
        font-weight: 500;
    }

    .security-badge i {
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Alert */
    .alert-error {
        background: rgba(220, 38, 38, 0.1);
        border-left: 4px solid var(--error-color);
        border-radius: var(--radius-lg);
        padding: 0.875rem 1rem;
        margin-bottom: 1.25rem;
        color: var(--error-color);
        display: flex;
        align-items: flex-start;
        gap: 0.625rem;
        font-size: 0.875rem;
        animation: shake 0.4s ease-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .alert-error i {
        font-size: 1.125rem;
        flex-shrink: 0;
    }

    /* Validation */
    .form-control.is-invalid {
        border-color: var(--error-color);
    }

    .form-control.is-valid {
        border-color: var(--success-color);
    }

    /* Word of Day */
    .word-of-day {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-light);
        text-align: center;
    }

    .word-of-day h6 {
        color: var(--text-muted);
        font-size: 0.8125rem;
        font-weight: 500;
        margin-bottom: 0.375rem;
    }

    .word-of-day p {
        color: var(--primary-blue);
        font-weight: 600;
        font-size: 0.9375rem;
        margin: 0;
    }

    /* ===== Footer - Redesigned ===== */
    .login-footer {
        position: relative;
        z-index: 100;
    }

    /* Footer Tricolor */
    .footer-tricolor {
        height: 4px;
        background: linear-gradient(90deg, var(--accent-saffron) 33.33%, white 33.33%, white 66.66%, var(--accent-green) 66.66%);
    }

    /* Main Footer */
    .footer-main {
        background: var(--primary-blue);
        color: white;
        padding: 1rem 0;
    }

    .footer-main .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-info {
        font-size: 0.8125rem;
        color: rgba(255,255,255,0.95);
    }

    .footer-info a {
        color: white;
        text-decoration: none;
    }

    .footer-info a:hover {
        text-decoration: underline;
    }

    .footer-badge {
        background: rgba(255,255,255,0.15);
        padding: 0.375rem 0.875rem;
        border-radius: var(--radius-md);
        font-size: 0.8125rem;
    }

    /* NeGD Credit Bar */
    .footer-negd {
        background: rgba(0, 26, 61, 0.9);
        padding: 0.625rem 0;
    }

    .footer-negd .container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .footer-negd a {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        color: rgba(255,255,255,0.9);
        text-decoration: none;
        font-size: 0.8125rem;
        transition: opacity var(--transition-base);
    }

    .footer-negd a:hover {
        opacity: 0.9;
        color: white;
    }

    .footer-negd img {
        height: 24px;
        width: auto;
    }

    /* ===== Carousel Controls (Hidden but accessible) ===== */
    .bg-carousel-container .carousel-control-prev,
    .bg-carousel-container .carousel-control-next {
        display: none;
    }

    .bg-carousel-container .carousel-indicators {
        display: none;
    }

    /* ===== Decorative Elements ===== */
    .floating-shapes {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 3;
        overflow: hidden;
    }

    .shape {
        position: absolute;
        background: rgba(255,255,255,0.03);
        border-radius: 50%;
        animation: float 20s infinite ease-in-out;
    }

    .shape-1 { width: 400px; height: 400px; top: -100px; left: -100px; animation-delay: 0s; }
    .shape-2 { width: 300px; height: 300px; bottom: -50px; right: -50px; animation-delay: -5s; }
    .shape-3 { width: 200px; height: 200px; top: 50%; left: 10%; animation-delay: -10s; }
    .shape-4 { width: 150px; height: 150px; top: 20%; right: 15%; animation-delay: -15s; }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        25% { transform: translate(20px, -20px) rotate(5deg); }
        50% { transform: translate(0, 20px) rotate(0deg); }
        75% { transform: translate(-20px, -10px) rotate(-5deg); }
    }

    /* ===== Responsive ===== */
    @media (max-width: 576px) {
        .header-main .container {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .header-left {
            flex-direction: column;
            border-right: none;
        }
        .header-govt {
            border-right: none;
            padding-right: 0;
        }
        .header-govt span { font-size: 0.75rem; }
        .header-lbsnaa img { height: 36px; max-width: 160px; }
        .header-right {
            flex-direction: column;
            width: 100%;
        }
        .header-digital-india { justify-content: center; }
        .header-digital-india img { height: 30px; }
        .header-utils { justify-content: center; }
        .footer-main .container { flex-direction: column; text-align: center; }
        .footer-negd .container { flex-direction: column; }
        
        .login-card {
            padding: 1.5rem;
            margin: 0.5rem;
            border-radius: var(--radius-xl);
        }
        
        .login-logo img { max-width: 160px; }
        .login-logo h1 { font-size: 1.25rem; }
        
    }

    @media (min-width: 576px) and (max-width: 768px) {
        .header-left { gap: 1rem; }
        .header-govt span { font-size: 0.75rem; }
        .header-lbsnaa img { height: 38px; max-width: 180px; }
        .login-card {
            max-width: 400px;
            padding: 2rem;
        }
    }

    @media (min-width: 768px) {
        .login-card {
            padding: 2.5rem;
        }
        .login-logo img { max-width: 220px; }
    }

    @media (min-width: 992px) {
        .login-card {
            max-width: 460px;
        }
    }

    @media (max-height: 700px) {
        .login-card {
            padding: 1.25rem 1.5rem;
        }
        .login-logo { margin-bottom: 1rem; }
        .login-logo img { max-width: 150px; }
        .login-logo h1 { font-size: 1.25rem; margin-top: 0.5rem; }
        .form-group { margin-bottom: 0.875rem; }
        .form-control { padding: 0.75rem; }
        .btn-login { padding: 0.875rem; }
        .word-of-day { margin-top: 1rem; padding-top: 0.75rem; }
    }

    /* Accessibility */
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
        .bg-carousel-container .carousel-item img {
            transition: none !important;
            transform: none !important;
        }
    }

    @media (prefers-contrast: high) {
        .login-card {
            background: white;
            border: 3px solid var(--primary-blue);
        }
        .form-control { border-width: 3px; }
    }

    @media print {
        .bg-carousel-container, .bg-overlay, .bg-pattern, .floating-shapes, .login-header, .login-footer { display: none !important; }
        .login-card { box-shadow: none; border: 2px solid #000; }
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

                <!-- Word of Day -->
                <div class="word-of-day">
                    <h6>
                        <i class="bi bi-translate" aria-hidden="true"></i>
                        आज का शब्द / Word of the Day
                    </h6>
                    <p>अर्हक अंक - Qualifying marks</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="login-footer" role="contentinfo">
            <div class="footer-tricolor"></div>
            <div class="footer-main">
                <div class="container">
                    <div class="footer-info">
                        <span>&copy; {{ date('Y') }} LBSNAA Mussoorie, Government of India. All Rights Reserved</span>
                        <span class="d-none d-md-inline mx-2">|</span>
                        <span class="d-none d-sm-inline">Support: <a href="mailto:support.lbsnaa@nic.in">support.lbsnaa@nic.in</a></span>
                    </div>
                    <div class="footer-badge">
                        <i class="bi bi-people-fill me-1" aria-hidden="true"></i>
                        Active Users: <strong id="activeCount">--</strong>
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
                                placeholder="Enter your password" name="password" required aria-required="true"
                                autocomplete="current-password">
                            <button type="button" class="btn input-group-text" id="togglePassword"
                                aria-label="Toggle password visibility">
                                <i class="material-icons menu-icon" aria-hidden="true">visibility</i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-start mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="keepLoggedIn" checked>
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
            </div>
            <div class="footer-negd">
                <div class="container">
                    <a href="https://negd.gov.in/" target="_blank" rel="noopener noreferrer" aria-label="Powered by National e-Governance Division">
                        <img src="{{ asset('images/negd.png') }}" alt="NeGD Logo" loading="lazy" onerror="this.style.display='none'">
                        <span>Powered by <strong>National e-Governance Division</strong>, MeitY</span>
                    </a>
                </div>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    <script>
    (function() {
        'use strict';

        // Password Toggle
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                const icon = this.querySelector('i');
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
                this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        }

        // Form Validation & Submit
        const form = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username');
                const password = document.getElementById('password');
                let isValid = true;

                [username, password].forEach(input => {
                    if (input) {
                        input.classList.remove('is-valid', 'is-invalid');
                        if (!input.value.trim()) {
                            input.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            input.classList.add('is-valid');
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.focus();
                    return;
                }

                if (loginBtn) {
                    loginBtn.classList.add('loading');
                    loginBtn.disabled = true;
                }
            });

            // Real-time validation
            form.querySelectorAll('input[required]').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid') && this.value.trim()) {
                        this.classList.remove('is-invalid');
                    }
                });
            });
        }
    }

        // Active Users
        const activeCount = document.getElementById('activeCount');
        if (activeCount) {
            activeCount.textContent = Math.floor(Math.random() * 80) + 40;
        }

        // Initialize Carousel with Ken Burns effect
        const carousel = document.getElementById('bgCarousel');
        if (carousel && window.bootstrap) {
            new bootstrap.Carousel(carousel, {
                interval: 6000,
                ride: 'carousel',
                pause: false
            });
        }

        // Accessibility announcement
        window.addEventListener('load', function() {
            const sr = document.createElement('div');
            sr.setAttribute('role', 'status');
            sr.setAttribute('aria-live', 'polite');
            sr.className = 'visually-hidden';
            sr.textContent = 'LBSNAA Login page loaded. Please enter your credentials.';
            document.body.appendChild(sr);
            setTimeout(() => sr.remove(), 3000);
        });
    })();
    </script>
</body>
</html>
