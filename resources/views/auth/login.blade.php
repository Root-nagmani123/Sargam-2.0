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
    <meta name="theme-color" content="#004a93">
    <meta name="color-scheme" content="light">
    
    <meta property="og:title" content="Login - Sargam | LBSNAA">
    <meta property="og:description" content="Secure portal access for LBSNAA community">
    <meta property="og:type" content="website">
    
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/logos/favicon.ico')}}">

    <!-- Preconnect to the CDN that serves render-blocking CSS/JS -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <!-- Preload the LCP hero carousel image so it downloads before the carousel HTML/CSS is parsed -->
    <link rel="preload" as="image" href="{{ asset('images/carasoul/1.webp') }}" fetchpriority="high">

    @php
        // Cache-busting versioned asset URL: appends the file's mtime so long-term
        // (1-year, immutable) caching is safe — the URL changes whenever the file does.
        $assetV = fn (string $p) => asset($p) . '?v=' . (@filemtime(public_path($p)) ?: 1);
    @endphp

    <!-- Bootstrap 5.3.6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="{{ $assetV('admin_assets/css/accesibility-style_v1.css') }}" rel="stylesheet">

    <title>Login - Sargam | LBSNAA</title>

    <style>
    /* ============================================
       MODERN FULLSCREEN LOGIN - ENHANCED UI/UX
       Bootstrap 5.3.6 + GIGW + WCAG 2.1 AAA
    ============================================ */

    :root {
        /* ── Brand palette — aligned with app design system (sargam-app.css --ds-*) ── */
        --primary: #004a93;          /* --ds-primary / --bs-primary */
        --primary-hover: #003d7a;    /* brand hover used across breadcrumb/course/calendar */
        --primary-light: #e6eff8;
        --primary-subtle: #f0f6fc;
        --secondary: #b12923;        /* --ds-secondary (brand red) */
        /* National-identity accents (GIGW) */
        --accent-saffron: #ff9933;
        --accent-green: #138808;
        --accent-orange: #ff6b35;    /* high-visibility focus ring (WCAG) */
        --text-heading: #0f172a;
        --text-body: #334155;
        --text-muted: #64748b;
        --text-light: #94a3b8;
        --surface: #ffffff;
        --surface-hover: #f8fafc;
        --border: #e2e8f0;
        --border-focus: #004a93;
        --success: #059669;
        --error: #dc2626;
        --shadow-card: 0 20px 60px -12px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1);
        --shadow-input: 0 1px 2px rgba(0,0,0,0.05);
        --shadow-input-focus: 0 0 0 4px rgba(0,74,147,0.12);
        --shadow-btn: 0 4px 14px rgba(0,74,147,0.35);
        --shadow-btn-hover: 0 8px 24px rgba(0,74,147,0.45);
        /* Radius — aligned to DS: inputs/buttons ~8px, card 16px (softened for auth surface) */
        --radius-sm: 0.25rem;   /* --ds-radius-1 */
        --radius-md: 0.5rem;    /* --ds-radius-2 */
        --radius-lg: 0.5rem;    /* inputs, buttons, badges */
        --radius-xl: 0.75rem;
        --radius-2xl: 0.875rem;
        --radius-3xl: 1rem;     /* login card */
        --transition: 250ms cubic-bezier(0.4, 0, 0.2, 1);
        --font-sans: 'Inter', 'Noto Sans Devanagari', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
        color: var(--text-body);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* ===== Skip Link ===== */
    .skip-to-content {
        position: absolute;
        top: -100%;
        left: 1rem;
        background: var(--text-heading);
        color: white;
        padding: 0.75rem 1.25rem;
        text-decoration: none;
        border-radius: 0 0 var(--radius-md) var(--radius-md);
        z-index: 9999;
        font-weight: 600;
        font-size: 0.875rem;
        transition: top var(--transition);
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
        filter: brightness(0.55) saturate(1.1);
        transition: transform 10s ease-out;
    }

    .bg-carousel-container .carousel-item.active img {
        transform: scale(1.08);
    }

    /* Overlay */
    .bg-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            160deg,
            rgba(0, 30, 61, 0.88) 0%,
            rgba(0, 74, 147, 0.72) 40%,
            rgba(0, 61, 122, 0.82) 100%
        );
        z-index: 1;
    }

    /* Mesh Gradient Pattern */
    .bg-pattern {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: 
            radial-gradient(ellipse at 20% 50%, rgba(255,153,51,0.08) 0%, transparent 50%),
            radial-gradient(ellipse at 80% 20%, rgba(19,136,8,0.06) 0%, transparent 50%),
            radial-gradient(ellipse at 50% 80%, rgba(0,74,147,0.1) 0%, transparent 50%);
        z-index: 2;
        pointer-events: none;
    }

    /* ===== Header ===== */
    .login-header {
        position: relative;
        z-index: 100;
    }

    .header-tricolor {
        height: 4px;
        background: linear-gradient(90deg, var(--accent-saffron) 33.33%, white 33.33%, white 66.66%, var(--accent-green) 66.66%);
    }

    .header-main {
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(16px) saturate(180%);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 0.625rem 0;
    }

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
        border-right: 1px solid var(--border);
    }

    .header-govt img {
        height: 28px;
        width: auto;
    }

    .header-govt span {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-heading);
        white-space: nowrap;
        letter-spacing: -0.01em;
    }

    .header-lbsnaa img {
        height: 40px;
        max-width: 280px;
        width: auto;
    }

    .header-utils a {
        padding: 0.4rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--text-muted);
        text-decoration: none;
        border-radius: var(--radius-md);
        transition: all var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .header-utils a:hover {
        background: var(--primary-subtle);
        color: var(--primary);
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

    /* ===== Login Card ===== */
    .login-card {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(24px) saturate(200%);
        -webkit-backdrop-filter: blur(24px) saturate(200%);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: var(--radius-3xl);
        box-shadow: var(--shadow-card);
        width: 100%;
        max-width: 460px;
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
        animation: cardEntry 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes cardEntry {
        from {
            opacity: 0;
            transform: translateY(24px) scale(0.96);
            filter: blur(4px);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: blur(0);
        }
    }

    /* Tricolor Top Accent */
    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, 
            var(--accent-saffron) 0%, var(--accent-saffron) 33.33%, 
            #ffffff 33.33%, #ffffff 66.66%, 
            var(--accent-green) 66.66%, var(--accent-green) 100%);
    }

    /* Logo Section */
    .login-logo {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-logo img {
        max-width: 180px;
        height: auto;
        margin-bottom: 0.75rem;
    }

    .login-logo h1 {
        color: var(--text-heading);
        font-size: 1.625rem;
        font-weight: 800;
        letter-spacing: -0.025em;
        margin-bottom: 0.25rem;
    }

    .login-logo .subtitle {
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 400;
    }

    /* ===== Form Styles ===== */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label-custom {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-heading);
        font-size: 0.875rem;
        letter-spacing: -0.01em;
    }

    .form-label-custom i {
        color: var(--primary);
        font-size: 0.9375rem;
    }

    .form-control-custom {
        width: 100%;
        padding: 0.8125rem 1rem;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-lg);
        font-size: 0.9375rem;
        font-weight: 400;
        color: var(--text-heading);
        background: var(--surface);
        box-shadow: var(--shadow-input);
        transition: all var(--transition);
        letter-spacing: -0.01em;
    }

    .form-control-custom:hover:not(:focus) {
        border-color: #cbd5e1;
        background: var(--surface-hover);
    }

    .form-control-custom:focus {
        border-color: var(--border-focus);
        box-shadow: var(--shadow-input-focus);
        outline: none;
        background: var(--surface);
    }

    .form-control-custom::placeholder {
        color: var(--text-light);
        font-weight: 400;
    }

    /* Input Group for Password */
    .password-input-group {
        position: relative;
        display: flex;
    }

    .password-input-group .form-control-custom {
        padding-right: 3.25rem;
        border-radius: var(--radius-lg);
    }

    .password-toggle-btn {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: var(--text-light);
        cursor: pointer;
        transition: color var(--transition);
        border-radius: 0 var(--radius-lg) var(--radius-lg) 0;
    }

    .password-toggle-btn:hover {
        color: var(--primary);
    }

    .form-hint {
        font-size: 0.75rem;
        color: var(--text-light);
        margin-top: 0.375rem;
        font-weight: 400;
    }

    /* ===== Submit Button ===== */
    .btn-login {
        width: 100%;
        padding: 0.875rem 1.5rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-size: 0.9375rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        cursor: pointer;
        transition: all var(--transition);
        box-shadow: var(--shadow-btn);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.625rem;
        position: relative;
        overflow: hidden;
    }

    .btn-login::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.1) 0%, transparent 60%);
        pointer-events: none;
    }

    .btn-login:hover {
        background: var(--primary-hover);
        box-shadow: var(--shadow-btn-hover);
        transform: translateY(-1px);
    }

    .btn-login:active {
        transform: translateY(0);
        box-shadow: var(--shadow-btn);
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

    .btn-login.loading::before {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border: 2.5px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        z-index: 2;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* Forgot Password */
    .forgot-link {
        color: var(--primary);
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 600;
        transition: all var(--transition);
    }

    .forgot-link:hover {
        color: var(--primary-hover);
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    /* Security Badge */
    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.25rem;
        padding: 0.625rem 1rem;
        background: linear-gradient(135deg, rgba(5,150,105,0.08) 0%, rgba(5,150,105,0.04) 100%);
        border: 1px solid rgba(5,150,105,0.15);
        border-radius: var(--radius-lg);
        color: var(--success);
        font-size: 0.8125rem;
        font-weight: 500;
    }

    .security-badge i {
        animation: securePulse 2.5s ease-in-out infinite;
    }

    @keyframes securePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(0.95); }
    }

    /* Alert */
    .alert-error {
        background: rgba(220, 38, 38, 0.06);
        border: 1px solid rgba(220, 38, 38, 0.15);
        border-left: 4px solid var(--error);
        border-radius: var(--radius-lg);
        padding: 0.875rem 1rem;
        margin-bottom: 1.5rem;
        color: var(--error);
        display: flex;
        align-items: flex-start;
        gap: 0.625rem;
        font-size: 0.875rem;
        font-weight: 500;
        animation: alertSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes alertSlide {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .alert-error i {
        font-size: 1.125rem;
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* Validation */
    .form-control-custom.is-invalid {
        border-color: var(--error);
        box-shadow: 0 0 0 4px rgba(220,38,38,0.08);
    }

    .form-control-custom.is-valid {
        border-color: var(--success);
        box-shadow: 0 0 0 4px rgba(5,150,105,0.08);
    }

    /* Word of Day */
    .word-of-day {
        margin-top: 1.75rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
        text-align: center;
    }

    .word-of-day .wod-label {
        color: var(--text-light);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.375rem;
    }

    .word-of-day .wod-text {
        color: var(--primary);
        font-weight: 700;
        font-size: 0.9375rem;
        margin: 0;
        letter-spacing: -0.01em;
    }

    /* ===== Footer ===== */
    .login-footer {
        position: relative;
        z-index: 100;
    }

    .footer-tricolor {
        height: 4px;
        background: linear-gradient(90deg, var(--accent-saffron) 33.33%, white 33.33%, white 66.66%, var(--accent-green) 66.66%);
    }

    .footer-main {
        background: rgba(15, 23, 42, 0.97);
        backdrop-filter: blur(12px);
        color: white;
        padding: 0.875rem 0;
    }

    .footer-info {
        font-size: 0.8125rem;
        color: rgba(255,255,255,0.85);
        font-weight: 400;
    }

    .footer-info a {
        color: rgba(255,255,255,0.95);
        text-decoration: none;
        font-weight: 500;
    }

    .footer-info a:hover {
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .footer-badge {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.15);
        padding: 0.375rem 0.875rem;
        border-radius: var(--radius-md);
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgba(255,255,255,0.9);
    }

    .footer-negd {
        background: rgba(10, 15, 30, 0.97);
        padding: 0.5rem 0;
    }

    .footer-negd a {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        font-size: 0.8125rem;
        font-weight: 400;
        transition: color var(--transition);
    }

    .footer-negd a:hover {
        color: white;
    }

    .footer-negd img {
        height: 22px;
        width: auto;
    }

    /* ===== GIGW Accessibility Toolbar ===== */
    .gigw-toolbar {
        background: var(--primary);
        padding: 0.375rem 0;
        position: relative;
        z-index: 101;
    }

    .gigw-toolbar .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .gigw-toolbar-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .gigw-toolbar-left a {
        color: rgba(255,255,255,0.9);
        font-size: 0.75rem;
        text-decoration: none;
        font-weight: 500;
        transition: color var(--transition);
    }

    .gigw-toolbar-left a:hover,
    .gigw-toolbar-left a:focus {
        color: white;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .gigw-toolbar-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .font-size-controls {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .font-size-controls span {
        color: rgba(255,255,255,0.8);
        font-size: 0.6875rem;
        font-weight: 500;
        margin-right: 0.25rem;
    }

    .font-size-btn {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.25);
        color: white;
        width: 26px;
        height: 26px;
        border-radius: var(--radius-sm);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.75rem;
        transition: all var(--transition);
        text-decoration: none;
    }

    .font-size-btn:hover,
    .font-size-btn:focus {
        background: rgba(255,255,255,0.3);
        color: white;
    }

    .font-size-btn.active {
        background: white;
        color: var(--primary);
    }

    .contrast-btn {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.25);
        color: white;
        padding: 0.25rem 0.625rem;
        border-radius: var(--radius-sm);
        font-size: 0.6875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .contrast-btn:hover,
    .contrast-btn:focus {
        background: rgba(255,255,255,0.3);
        color: white;
    }

    .lang-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .lang-toggle a {
        color: rgba(255,255,255,0.8);
        font-size: 0.6875rem;
        font-weight: 600;
        text-decoration: none;
        padding: 0.2rem 0.5rem;
        border-radius: var(--radius-sm);
        transition: all var(--transition);
    }

    .lang-toggle a:hover,
    .lang-toggle a:focus {
        background: rgba(255,255,255,0.15);
        color: white;
    }

    .lang-toggle a.active-lang {
        background: white;
        color: var(--primary);
    }

    .lang-toggle .separator {
        color: rgba(255,255,255,0.4);
        font-size: 0.6875rem;
    }

    /* National Emblem */
    .national-emblem {
        height: 36px;
        width: auto;
        margin-right: 0.5rem;
    }

    /* GIGW Footer Links */
    .footer-links {
        background: rgba(15, 23, 42, 0.95);
        padding: 0.625rem 0;
        border-top: 1px solid rgba(255,255,255,0.08);
    }

    .footer-links .container {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
    }

    .footer-links a {
        color: rgba(255,255,255,0.75);
        font-size: 0.75rem;
        text-decoration: none;
        font-weight: 400;
        transition: color var(--transition);
        padding: 0.125rem 0;
    }

    .footer-links a:hover,
    .footer-links a:focus {
        color: white;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .footer-links .link-separator {
        color: rgba(255,255,255,0.3);
        font-size: 0.625rem;
    }

    .footer-last-updated {
        font-size: 0.6875rem;
        color: rgba(255,255,255,0.6);
        text-align: center;
        padding-top: 0.375rem;
    }

    /* High Contrast Mode */
    body.high-contrast {
        --primary: #000000;
        --primary-hover: #1a1a1a;
        --text-heading: #000000;
        --text-body: #000000;
        --text-muted: #333333;
        --text-light: #555555;
        --surface: #ffffff;
        --border: #000000;
    }

    body.high-contrast .login-card {
        background: #ffffff;
        border: 3px solid #000000;
        box-shadow: none;
    }

    body.high-contrast .form-control-custom {
        border: 2px solid #000000;
    }

    body.high-contrast .btn-login {
        background: #000000;
        box-shadow: none;
    }

    body.high-contrast .gigw-toolbar {
        background: #000000;
    }

    body.high-contrast .header-main {
        background: #ffffff;
        border-bottom: 2px solid #000;
    }

    body.high-contrast .bg-carousel-container {
        display: none;
    }

    body.high-contrast .bg-overlay {
        background: #333333;
    }

    /* Font Size Scaling */
    html.font-size-small { font-size: 87.5%; }
    html.font-size-normal { font-size: 100%; }
    html.font-size-large { font-size: 112.5%; }
    html.font-size-xlarge { font-size: 125%; }

    /* ===== Carousel Controls (Hidden) ===== */
    .bg-carousel-container .carousel-control-prev,
    .bg-carousel-container .carousel-control-next,
    .bg-carousel-container .carousel-indicators {
        display: none;
    }

    /* ===== Floating Shapes ===== */
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
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 70%);
        animation: floatShape 25s infinite ease-in-out;
    }

    .shape-1 { width: 500px; height: 500px; top: -150px; left: -150px; animation-delay: 0s; }
    .shape-2 { width: 350px; height: 350px; bottom: -80px; right: -80px; animation-delay: -6s; }
    .shape-3 { width: 250px; height: 250px; top: 45%; left: 8%; animation-delay: -12s; }
    .shape-4 { width: 180px; height: 180px; top: 15%; right: 12%; animation-delay: -18s; }

    @keyframes floatShape {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(15px, -20px) scale(1.02); }
        50% { transform: translate(-10px, 15px) scale(0.98); }
        75% { transform: translate(-15px, -10px) scale(1.01); }
    }

    /* ===== Responsive ===== */
    @media (max-width: 576px) {
        .login-card {
            padding: 1.75rem 1.5rem;
            margin: 0.5rem;
            border-radius: var(--radius-2xl);
        }
        .login-logo img { max-width: 150px; }
        .login-logo h1 { font-size: 1.375rem; }
        .header-govt span { font-size: 0.6875rem; }
        .gigw-toolbar .container { justify-content: center; }
        .gigw-toolbar-left { display: none; }
        .footer-links .container { gap: 0.25rem 0.75rem; }
        .national-emblem { height: 28px; }
    }

    @media (min-width: 576px) and (max-width: 768px) {
        .login-card {
            max-width: 420px;
            padding: 2.25rem;
        }
    }

    @media (min-width: 768px) {
        .login-card {
            padding: 2.75rem;
        }
        .login-logo img { max-width: 200px; }
    }

    @media (min-width: 992px) {
        .login-card {
            max-width: 480px;
        }
    }

    @media (max-height: 700px) {
        .login-card {
            padding: 1.5rem 1.75rem;
        }
        .login-logo { margin-bottom: 1.25rem; }
        .login-logo img { max-width: 140px; }
        .login-logo h1 { font-size: 1.25rem; }
        .form-group { margin-bottom: 1rem; }
        .form-control-custom { padding: 0.6875rem 0.875rem; }
        .btn-login { padding: 0.75rem; }
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
            border: 3px solid var(--primary);
        }
        .form-control-custom { border-width: 3px; }
    }

    @media print {
        .bg-carousel-container, .bg-overlay, .bg-pattern, .floating-shapes, .login-header, .login-footer { display: none !important; }
        .login-card { box-shadow: none; border: 2px solid #000; }
    }
    </style>
</head>

<body class="bg-dark">
    <a href="#login-form" class="skip-to-content">Skip to Main Content</a>

    <div class="login-wrapper">
        <!-- GIGW: Accessibility Toolbar -->
        <div class="gigw-toolbar" role="navigation" aria-label="Accessibility Options">
            <div class="container">
                <div class="gigw-toolbar-left">
                    <a href="https://screenreaderaccess.com" target="_blank" rel="noopener noreferrer" title="Screen Reader Access Information">
                        <i class="bi bi-ear" aria-hidden="true"></i> Screen Reader Access
                    </a>
                    <a href="https://www.india.gov.in" target="_blank" rel="noopener noreferrer" title="National Portal of India">
                        <i class="bi bi-globe2" aria-hidden="true"></i> india.gov.in
                    </a>
                </div>
                <div class="gigw-toolbar-right">
                    <div class="font-size-controls" role="group" aria-label="Text Size Controls">
                        <span>Text Size:</span>
                        <button type="button" class="font-size-btn" data-size="small" aria-label="Decrease text size" title="Decrease Text Size">A-</button>
                        <button type="button" class="font-size-btn active" data-size="normal" aria-label="Normal text size" title="Normal Text Size">A</button>
                        <button type="button" class="font-size-btn" data-size="large" aria-label="Increase text size" title="Increase Text Size">A+</button>
                    </div>
                    <button type="button" class="contrast-btn" id="contrastToggle" aria-label="Toggle high contrast" title="High Contrast Mode">
                        <i class="bi bi-circle-half" aria-hidden="true"></i> Contrast
                    </button>
                    <div class="lang-toggle" role="group" aria-label="Language Selection">
                        <a href="#" class="active-lang" lang="en" title="English">EN</a>
                        <span class="separator">|</span>
                        <a href="#" lang="hi" title="हिन्दी">हिन्दी</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Background Carousel -->
        <div class="bg-carousel-container" aria-hidden="true">
            <div id="bgCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="false">
                <div class="carousel-inner">
                    @for($i = 1; $i <= 10; $i++)
                    <div class="carousel-item {{ $i === 1 ? 'active' : '' }}">
                        {{-- Only the hero (slide 1) loads on first paint; slides 2-10 hold their
                             URL in data-src and are fetched on demand just before they slide in
                             (see the carousel init script at the bottom of the page). --}}
                        @if($i === 1)
                        <img src="{{ asset('images/carasoul/1.webp') }}"
                            alt="" loading="eager" fetchpriority="high" decoding="async">
                        @else
                        <img data-src="{{ asset('images/carasoul/' . $i . '.webp') }}"
                            alt="" loading="lazy" decoding="async">
                        @endif
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
                    <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2">
                        <div class="header-left">
                            <!-- GIGW: National Emblem -->
                            <img src="{{ asset('admin_assets/images/logos/emblem-dark.png') }}"
                                alt="National Emblem of India - Satyameva Jayate"
                                class="national-emblem"
                                loading="eager"
                                onerror="this.style.display='none'">
                            <div class="header-govt">
                                <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/330px-Flag_of_India.svg.png"
                                    alt="National Flag of India" loading="eager" onerror="this.style.display='none'">
                                <span lang="hi">भारत सरकार</span>
                                <span class="d-none d-sm-inline">| Government of India</span>
                            </div>
                            <a href="{{ url('/') }}" class="d-none d-lg-inline-flex align-items-center text-decoration-none" aria-label="LBSNAA Home">
                                {{-- Right-sized web variants (400px) instead of the 1193px shared logo.png,
                                     which stays in place for PDF/print exports. WebP with PNG fallback. --}}
                                <picture>
                                    <source srcset="{{ $assetV('admin_assets/images/logos/logo-web.webp') }}" type="image/webp">
                                    <img src="{{ $assetV('admin_assets/images/logos/logo-web.png') }}"
                                        alt="LBSNAA - Lal Bahadur Shastri National Academy of Administration" loading="eager" onerror="this.style.display='none'" class="header-lbsnaa" style="width: 180px; height: auto;">
                                </picture>
                            </a>
                        </div>
                        <div class="d-none d-lg-flex align-items-center">
                            <nav class="header-utils d-flex align-items-center gap-1" aria-label="Utility Navigation">
                                <a href="#login-form"><i class="bi bi-arrow-down-circle" aria-hidden="true"></i> Skip to Content</a>
                                <a href="#" id="accessibilityTrigger" role="button"><i class="bi bi-universal-access" aria-hidden="true"></i> Accessibility</a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content" role="main">
            <div class="login-card">
                <!-- Logo & Title -->
                <div class="login-logo">
                    <img src="{{ $assetV('admin_assets/images/logos/logo.svg') }}"
                        alt="Sargam - LBSNAA Portal"
                        loading="eager"
                        class="d-block mx-auto">
                    <h1 id="login-form">Welcome Back</h1>
                    <p class="subtitle mb-0">Sign in to access your LBSNAA account</p>
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
                        <label for="username" class="form-label-custom">
                            <i class="bi bi-person-fill" aria-hidden="true"></i>
                            Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                            class="form-control-custom @error('username') is-invalid @enderror" 
                            id="username"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Enter your username or ID"
                            autocomplete="username"
                            required
                            autofocus>
                        <div class="form-hint">Your official registration number or ID</div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="password" class="form-label-custom mb-0">
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                Password <span class="text-danger">*</span>
                            </label>
                        </div>
                        <div class="password-input-group">
                            <input type="password" 
                                class="form-control-custom @error('password') is-invalid @enderror" 
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>
                            <button type="button" class="password-toggle-btn" id="togglePassword" aria-label="Show password">
                                <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                            </button>
                            
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <a href="{{ route('password.request') ?? '#' }}" class="forgot-link">
                                Forgot Password?
                            </a>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-login mt-1" id="loginBtn">
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
                    <p class="wod-label mb-1">
                        <i class="bi bi-translate" aria-hidden="true"></i>
                        आज का शब्द / Word of the Day
                    </p>
                    <p class="wod-text">अर्हक अंक - Qualifying marks</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="login-footer" role="contentinfo">
            <div class="footer-tricolor"></div>
            <div class="footer-main">
                <div class="container">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 w-100">
                        <div class="footer-info">
                            <span>&copy; {{ date('Y') }} LBSNAA Mussoorie, Government of India. All Rights Reserved</span>
                            <span class="d-none d-md-inline mx-2">|</span>
                            <span class="d-none d-sm-inline">Support: <a href="mailto:support.lbsnaa@nic.in">support[dot]lbsnaa[at]nic[dot]in</a></span>
                            <span class="d-none d-sm-inline mx-2">|</span> <span>Phone: 135-2222346 (Mon–Fri, 9:00 AM–5:30 PM)</span>
                        </div>
                        <div class="footer-badge d-inline-flex align-items-center gap-1">
                            <i class="bi bi-people-fill" aria-hidden="true"></i>
                            Active Users: <strong id="activeCount">--</strong>
                        </div>
                    </div>
                </div>
            </div>
            <!-- GIGW: Mandatory Footer Links -->
            <div class="footer-links">
                <div class="container">
                    <a href="#" title="Website Policies">Web Information Manager</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Privacy Policy">Privacy Policy</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Terms and Conditions">Terms & Conditions</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Copyright Policy">Copyright Policy</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Hyperlinking Policy">Hyperlinking Policy</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Accessibility Statement">Accessibility Statement</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Disclaimer">Disclaimer</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Help">Help</a>
                    <span class="link-separator" aria-hidden="true">|</span>
                    <a href="#" title="Sitemap">Sitemap</a>
                    <div class="footer-last-updated w-100 mt-1">
                        <span>Last Updated: {{ date('d M Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="footer-negd">
                <div class="container text-center">
                    <a href="https://negd.gov.in/" target="_blank" rel="noopener noreferrer" aria-label="Powered by National e-Governance Division">
                        <img src="https://cdn.digitalindiacorporation.in/wp-content/themes/di-child/assets/images/dilogonew.svg.gzip" alt="NeGD Logo" loading="lazy" onerror="this.style.display='none'">
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

        // Active Users
        const activeCount = document.getElementById('activeCount');
        if (activeCount) {
            activeCount.textContent = Math.floor(Math.random() * 80) + 40;
        }

        // Initialize Carousel with Ken Burns effect + on-demand (lazy) slide loading.
        // Only the hero slide ships a real src; slides 2-10 carry their URL in data-src
        // and are fetched just before they scroll into view, so the initial page load
        // downloads a single image instead of all ten.
        const carousel = document.getElementById('bgCarousel');
        if (carousel && window.bootstrap) {
            const items = carousel.querySelectorAll('.carousel-item');

            // Swap a slide's data-src into src exactly once.
            const loadSlide = (index) => {
                const item = items[index];
                if (!item) return;
                const img = item.querySelector('img[data-src]');
                if (img) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
            };

            // Fetch the incoming slide's image right before Bootstrap transitions to it.
            carousel.addEventListener('slide.bs.carousel', (e) => loadSlide(e.to));

            new bootstrap.Carousel(carousel, {
                interval: 6000,
                ride: 'carousel',
                pause: false
            });

            // After the page settles, quietly prefetch slide 2 so the first
            // transition is seamless (guarded so it never competes with the hero).
            const prefetchNext = () => loadSlide(1);
            if ('requestIdleCallback' in window) {
                requestIdleCallback(prefetchNext, { timeout: 3000 });
            } else {
                setTimeout(prefetchNext, 2000);
            }
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

        // GIGW: Font Size Controls
        const fontBtns = document.querySelectorAll('.font-size-btn');
        fontBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const size = this.dataset.size;
                const html = document.documentElement;
                html.classList.remove('font-size-small', 'font-size-normal', 'font-size-large', 'font-size-xlarge');
                html.classList.add('font-size-' + size);
                fontBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Store preference
                try { localStorage.setItem('gigw-font-size', size); } catch(e) {}
                // Announce change
                announceChange('Text size changed to ' + size);
            });
        });

        // Restore font size preference
        try {
            const savedSize = localStorage.getItem('gigw-font-size');
            if (savedSize) {
                document.documentElement.classList.add('font-size-' + savedSize);
                fontBtns.forEach(b => {
                    b.classList.toggle('active', b.dataset.size === savedSize);
                });
            }
        } catch(e) {}

        // GIGW: High Contrast Toggle
        const contrastBtn = document.getElementById('contrastToggle');
        if (contrastBtn) {
            contrastBtn.addEventListener('click', function() {
                document.body.classList.toggle('high-contrast');
                const isHC = document.body.classList.contains('high-contrast');
                this.setAttribute('aria-pressed', isHC);
                try { localStorage.setItem('gigw-contrast', isHC ? 'high' : 'normal'); } catch(e) {}
                announceChange(isHC ? 'High contrast mode enabled' : 'Normal contrast mode enabled');
            });

            // Restore contrast preference
            try {
                if (localStorage.getItem('gigw-contrast') === 'high') {
                    document.body.classList.add('high-contrast');
                    contrastBtn.setAttribute('aria-pressed', 'true');
                }
            } catch(e) {}
        }

        // Accessibility live announcement helper
        function announceChange(message) {
            const announcer = document.createElement('div');
            announcer.setAttribute('role', 'status');
            announcer.setAttribute('aria-live', 'assertive');
            announcer.className = 'visually-hidden';
            announcer.textContent = message;
            document.body.appendChild(announcer);
            setTimeout(() => announcer.remove(), 2000);
        }
    })();
    </script>
</body>
</html>