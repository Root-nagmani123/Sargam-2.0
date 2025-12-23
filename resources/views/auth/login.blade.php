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

    <title>Login - Sargam | Lal Bahadur Shastri National Academy of Administration</title>

    <style>
    /* GIGW Color Palette Focus (High Contrast) - Enhanced */
    :root {
        --primary-blue: #004a93;
        /* Used for main branding and primary action */
        --primary-blue-light: #e8f1f8;
        /* Light background pattern */
        --primary-blue-dark: #003366;
        --primary-blue-darker: #002147;
        --text-primary: #1a1a1a;
        --text-secondary: #5a6c7d;
        --accent-orange: #ff6600;
        /* High-contrast focus/accessibility - GIGW compliant */
        --accent-gold: #ffa500;
        --border-color: #e0e6ed;
        --border-light: #f0f3f7;
        --success-color: #28a745;
        --error-color: #dc3545;
        --warning-color: #ffc107;
        --info-color: #17a2b8;
        --transition-smooth: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.04);
        --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.15);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        height: 100%;
        overflow: hidden;
    }

    #main-wrapper {
        height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    body {
        font-family: 'Inter', 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.65;
        color: var(--text-primary);
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f0f4f8 100%);
        height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        letter-spacing: 0.01em;
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

    /* GIGW Top Header Bar (Blue) - Enhanced Modern Design */
    .gigw-header-top {
        background: linear-gradient(135deg, var(--primary-blue) 0%, #003d7a 50%, var(--primary-blue-dark) 100%);
        color: white;
        padding: 8px 0;
        font-size: 13px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
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

    /* Main Header (Logo Bar) - Modern Glassmorphism Design */
    .main-header-nav {
        background: white;
        border-bottom: 1px solid var(--border-light);
        padding: 16px 0;
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(12px);
        background-color: rgba(255, 255, 255, 0.98);
        transition: var(--transition-smooth);
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
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        height: 100%;
        overflow-y: auto;
    }

    /* Login Card - Modern Glassmorphism & Enhanced Design */
    .login-card-image {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(0, 74, 147, 0.08);
        border-radius: 16px;
        box-shadow: var(--shadow-xl);
        max-width: 500px;
        width: 100%;
        padding: 32px;
        text-align: center;
        transition: var(--transition-smooth);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
        max-height: 95vh;
        overflow-y: auto;
    }

    .login-card-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--primary-blue) 0%, #0066cc 33%, var(--accent-orange) 66%, var(--accent-gold) 100%);
        box-shadow: 0 2px 8px rgba(0, 74, 147, 0.3);
    }

    .login-card-image:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .login-card-image h2 {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 32px;
        margin-bottom: 10px;
        letter-spacing: -0.8px;
        line-height: 1.2;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
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
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
        transition: color 0.2s;
        letter-spacing: 0.02em;
    }

    .form-label i {
        color: var(--primary-blue);
        margin-right: 6px;
    }

    .form-control {
        padding: 14px 18px;
        border-radius: 10px;
        border: 2px solid var(--border-color);
        font-size: 15px;
        transition: var(--transition-smooth);
        background-color: #f8fafb;
        font-weight: 400;
        line-height: 1.5;
    }

    .form-control:focus {
        background-color: white;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 4px rgba(0, 74, 147, 0.12), 0 4px 12px rgba(0, 74, 147, 0.08) !important;
        transform: translateY(-1px);
    }

    .form-control::placeholder {
        color: #adb5bd;
        font-size: 14px;
    }

    .input-group .form-control {
        border-right: none;
    }

    .password-toggle-btn {
        background-color: #f8fafb;
        border: 2px solid var(--border-color);
        border-left: none;
        border-radius: 0 10px 10px 0;
        color: var(--text-secondary);
        padding: 0 16px;
        cursor: pointer;
        transition: var(--transition-smooth);
        font-size: 20px;
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
        padding: 16px 0;
        transition: var(--transition-smooth);
        margin-top: 28px;
        border-radius: 12px;
        cursor: pointer;
        font-size: 17px;
        letter-spacing: 0.8px;
        box-shadow: 0 6px 20px rgba(0, 74, 147, 0.25);
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
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
        background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue-darker) 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 28px rgba(0, 74, 147, 0.35);
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
        background: linear-gradient(135deg, var(--primary-blue) 0%, #003d7a 50%, var(--primary-blue-dark) 100%);
        color: #e8eef3;
        padding: 20px 0;
        font-size: 13px;
        text-align: center;
        margin-top: auto;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.12);
        border-top: 2px solid rgba(255, 255, 255, 0.1);
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
        width: 22px;
        height: 22px;
        margin-top: 1px;
        border: 2px solid var(--border-color);
        border-radius: 6px;
        cursor: pointer;
        transition: var(--transition-smooth);
        background-color: white;
    }

    .form-check-input:checked {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 74, 147, 0.3);
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
        gap: 8px;
        color: var(--success-color);
        font-weight: 600;
        font-size: 13px;
        margin-top: 18px;
        padding: 10px 16px;
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.08) 0%, rgba(40, 167, 69, 0.12) 100%);
        border-radius: 8px;
        border: 1px solid rgba(40, 167, 69, 0.15);
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
        padding: 32px;
        text-align: left;
        position: relative;
        overflow-y: auto;
        background: white;
        border: 1px solid rgba(0, 74, 147, 0.1);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        animation: slideInUp 0.5s ease-out;
        max-height: 95vh;
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
        height: 5px;
        background: linear-gradient(90deg, var(--primary-blue) 0%, #0066cc 33%, var(--accent-orange) 66%, var(--accent-gold) 100%);
        box-shadow: 0 2px 8px rgba(0, 74, 147, 0.3);
    }

    .login-card-enhanced h2 {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 32px;
        margin-bottom: 10px;
        text-align: center;
        letter-spacing: -0.8px;
        line-height: 1.2;
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
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
        background-color: #f8fafb;
        border-left: none;
        cursor: pointer;
        color: var(--text-secondary);
        border-radius: 0 10px 10px 0;
        transition: var(--transition-smooth);
        border: 2px solid var(--border-color);
        border-left: none;
        padding: 0 16px;
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
        padding: 16px;
        border-radius: 12px;
        transition: var(--transition-smooth);
        color: white;
        font-size: 17px;
        letter-spacing: 0.8px;
        box-shadow: 0 6px 20px rgba(0, 74, 147, 0.25);
        cursor: pointer;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
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
        background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue-darker) 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 28px rgba(0, 74, 147, 0.35);
    }

    .login-button-enhanced:active {
        transform: translateY(0);
    }

    /* Forgot Password Link */
    .forgot-password-link {
        color: var(--primary-blue) !important;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: var(--transition-smooth);
        position: relative;
        padding-bottom: 2px;
    }

    .forgot-password-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--primary-blue);
        transition: width 0.3s ease;
    }

    .forgot-password-link:hover::after,
    .forgot-password-link:focus::after {
        width: 100%;
    }

    .forgot-password-link:hover,
    .forgot-password-link:focus {
        color: var(--primary-blue-dark) !important;
    }

    /* Carousel Enhancements */
    #carouselExampleFade,
    #carouselExampleFade .carousel-inner,
    #carouselExampleFade .carousel-item {
        height: 100% !important;
    }

    #carouselExampleFade .carousel-item img {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }

    .carasoul-image {
        object-fit: cover;
        width: 100%;
        height: 100% !important;
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
        background: rgba(0, 0, 0, 0.4);
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-smooth);
        backdrop-filter: blur(4px);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        background: rgba(0, 74, 147, 0.85);
        border-color: rgba(255, 255, 255, 0.4);
        transform: scale(1.1);
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        filter: brightness(1.2);
    }

    /* Responsive Design - Tablet & Mobile */
    /* Container Management */
    .container-fluid {
        height: 100%;
        overflow: hidden;
    }

    .container-fluid .row {
        height: 100%;
        overflow: hidden;
    }

    .container-fluid .row > div {
        height: 100%;
    }

    @media (max-width: 991.98px) {
        .login-page-wrapper {
            padding: 10px;
        }

        .main-header-nav .navbar-brand {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 575.98px) {
        .login-card-enhanced {
            padding: 20px 16px;
            border-radius: 12px;
            max-height: 92vh;
        }

        .login-page-wrapper {
            padding: 8px;
        }

        .login-card-enhanced h2 {
            font-size: 26px;
        }

        .login-card-enhanced p {
            font-size: 14px;
        }

        .form-label {
            font-size: 13px;
        }

        .form-control {
            font-size: 14px;
            padding: 12px 14px;
        }

        .login-button-enhanced {
            padding: 14px;
            font-size: 16px;
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
    }

    /* Dark Mode Preparation (for future enhancement) */
    @media (prefers-color-scheme: dark) {
        :root {
            --text-primary: #e0e0e0;
            --text-secondary: #b0b0b0;
            --border-color: #3a3a3a;
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

                            <div class="d-flex flex-column lh-sm">
                               <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png"
                                    alt="Lal Bahadur Shastri National Academy of Administration"
                                    class="brand-logo img-fluid d-none d-lg-block d-dark-none" width="300"
                                    height="auto">
                            </div>
                        </a>

                        <!-- Right: LBSNAA Logo -->
                        <div class="d-flex justify-content-end align-items-center">
                            <a href="{{ route('login') }}"
                                class="brand-link d-flex align-items-center gap-3 text-decoration-none"
                                aria-label="Login to LBSNAA Portal">

                                <!-- Light Mode Logo -->
                                

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
        <div class="container-fluid" style="flex: 1; display: flex; flex-direction: column;">
            <div class="row g-0" style="flex: 1;">
                <div class="col-lg-4 col-12 d-flex align-items-center justify-content-center bg-light">
                    <main class="login-page-wrapper">
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