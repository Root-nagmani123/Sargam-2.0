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
        --text-primary: #212529;
        --text-secondary: #6c757d;
        --accent-orange: #ff6b35;
        /* High-contrast focus/accessibility */
        --border-color: #dee2e6;
    }

    body {
        font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.6;
        color: var(--text-primary);
        background-color: #ffffff;
        min-height: 100vh;
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
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 0 0 4px 0;
        z-index: 9999;
        transition: top 0.3s;
    }

    .skip-to-content:focus {
        top: 0;
        outline: 3px solid var(--accent-orange);
        outline-offset: 2px;
    }

    a:focus,
    button:focus,
    input:focus,
    .form-check-input:focus,
    .dropdown-toggle:focus {
        outline: 3px solid var(--accent-orange) !important;
        outline-offset: 2px !important;
        box-shadow: none !important;
    }

    /* GIGW Top Header Bar (Blue) */
    .gigw-header-top {
        background-color: var(--primary-blue);
        color: white;
        padding: 4px 0;
        font-size: 13px;
    }

    .gigw-header-top a {
        color: white;
        text-decoration: none;
        padding: 4px 8px;
        margin-inline-end: 4px;
        transition: background-color 0.2s;
    }

    .gigw-header-top a:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    /* Main Header (Logo Bar) */
    .main-header-nav {
        background: white;
        border-bottom: 1px solid var(--border-color);
        padding: 10px 0;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    }

    .logo-text {
        color: #333;
        font-size: 14px;
        font-weight: 500;
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
    }

    /* GIGW Font Size Adjusters */
    .font-size-adjuster .btn {
        font-weight: 700;
        font-size: 14px;
        padding: 4px 8px;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .font-size-adjuster .btn:hover {
        background-color: #f0f0f0;
    }

    .login-btn-header {
        background-color: var(--primary-blue);
        color: white;
        font-weight: 600;
        padding: 8px 20px;
        border-radius: 4px;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .login-btn-header:hover {
        background-color: #003366;
        color: white;
    }

    /* Main Content & Background Pattern */
    .login-page-wrapper {
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Login Card - Replicating the Image Style */
    .login-card-image {
        background: white;
        border: 1px solid #ddd;
        /* Subtle border */
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        max-width: 480px;
        width: 100%;
        padding: 30px;
        text-align: center;
    }

    .login-card-image h2 {
        color: var(--primary-blue);
        font-weight: 600;
        font-size: 24px;
        margin-bottom: 8px;
    }

    .login-card-image p {
        color: var(--text-secondary);
        font-size: 15px;
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        text-align: left;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .form-control {
        padding: 12px;
        border-radius: 4px;
    }

    .password-toggle-btn {
        background-color: white;
        border: 1px solid var(--border-color);
        border-left: none;
        border-radius: 0 4px 4px 0;
        color: var(--text-secondary);
        padding: 0 12px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .password-toggle-btn:hover {
        background-color: #f8f9fa;
    }

    .login-button {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
        font-weight: 600;
        padding: 10px 0;
        transition: background-color 0.2s;
        margin-top: 20px;
    }

    .login-button:hover {
        background-color: #003366;
        border-color: #003366;
    }

    /* GIGW Footer */
    .gigw-footer {
        background-color: #004a93;
        color: #ddd;
        padding: 10px 0;
        font-size: 13px;
        text-align: center;
    }

    .gigw-footer a {
        color: #ddd;
        text-decoration: none;
        margin-left: 15px;
    }

    .gigw-footer a:hover {
        color: white;
        text-decoration: underline;
    }

    /* Language Dropdown Adjustment */
    .language-dropdown .btn {
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    .language-dropdown .btn:focus {
        box-shadow: none !important;
    }

    @media (max-width: 991.98px) {
        .main-header-nav .navbar-collapse {
            text-align: center;
            border-top: 1px solid var(--border-color);
            margin-top: 10px;
            padding-top: 10px;
        }
    }

    /* --- GIGW-Based Styling for Login Card ---
    (Assuming parent body/wrapper styles define:
     --primary-blue: #004a93;
     --accent-orange: #ff6b35; for focus) 
    */

    .login-card-enhanced {
        background: #ffffff;
        border-radius: 12px;
        /* Smoother corners */
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        width: 100%;
        padding: 30px 40px;
        /* More padding */
        text-align: left;
        /* Align text left for form readability */
        height: 100vh;
    }

    .login-card-enhanced h2 {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 28px;
        margin-bottom: 4px;
        text-align: center;
    }

    .login-card-enhanced p {
        color: #6c757d;
        font-size: 15px;
        margin-bottom: 30px;
        text-align: center;
    }

    /* GIGW: Focus outline is crucial */
    .form-control:focus,
    .btn:focus,
    .form-check-input:focus,
    a:focus {
        border-color: var(--primary-blue) !important;
        box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.5) !important;
        /* Using accent-orange for high-visibility focus ring */
        outline: none;
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
    }

    .form-label i {
        margin-right: 8px;
        color: var(--primary-blue);
    }

    .required-indicator {
        color: red;
        margin-left: 4px;
        font-weight: 400;
    }

    .input-group-text {
        background-color: #f8f9fa;
        border-left: none;
        cursor: pointer;
        color: #6c757d;
        border-radius: 0 6px 6px 0;
    }

    .input-group-text:hover {
        background-color: #e9ecef;
    }

    .login-button-enhanced {
        background-color: var(--primary-blue);
        border: none;
        font-weight: 700;
        padding: 12px;
        border-radius: 6px;
        transition: background-color 0.2s, transform 0.2s;
    }

    .login-button-enhanced:hover {
        background-color: #003366;
        /* Darker blue on hover */
        transform: translateY(-1px);
    }

    .forgot-password-link {
        color: var(--primary-blue) !important;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
    }

    .forgot-password-link:hover {
        text-decoration: underline;
    }

    /* Login Carousel enhancements */
    #carouselExampleFade,
    #carouselExampleFade .carousel-inner,
    #carouselExampleFade .carousel-item {
        height: 100vh !important;
    }

    #carouselExampleFade .carousel-item img {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }

    @media (max-width: 991.98px) {

        #carouselExampleFade,
        #carouselExampleFade .carousel-inner,
        #carouselExampleFade .carousel-item {
            height: 320px;
        }
    }

    .carasoul-image {
        object-fit: cover;
        width: 100%;
        height: 100vh !important;
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
                            <a href="{{ route('login') }}" class="d-flex align-items-center text-decoration-none"
                                aria-label="Login to LBSNAA Portal">

                                <!-- Light Mode Logo -->
                                <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo"
                                    class="img-fluid d-none d-dark-none d-lg-block" width="240">

                                <!-- Dark Mode Logo -->
                                <img src="{{ asset('admin_assets/images/logos/logo.svg') }}"
                                    alt="LBSNAA Logo - Dark Mode" class="img-fluid" width="240">
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
                                <img src="{{ asset('images/carasoul/1.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/2.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/3.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item" data-bs-interval="40000">
                                <img src="{{ asset('images/carasoul/4.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/5.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/6.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/7.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/8.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/9.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/10.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/11.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/12.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/13.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/14.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/15.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/16.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/17.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/18.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/19.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/20.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/21.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/22.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/23.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/24.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/25.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/26.jpg') }}"
                                    class="d-block w-100 img-fluid carasoul-image" alt="...">
                            </div>
                            <div class="carousel-item">
                                <img src="{{ asset('images/carasoul/27.jpg') }}"
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
        // UX Script: Password visibility toggle
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('passwordInput');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
                this.setAttribute('aria-label', 'Show password');
            }
        });
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
                /* swallow */ }
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

    // Function to handle password visibility toggle for both fields
    function setupPasswordToggle(toggleButtonId, passwordInputId) {
        const toggleButton = document.getElementById(toggleButtonId);
        const passwordInput = document.getElementById(passwordInputId);

        if (toggleButton && passwordInput) {
            toggleButton.addEventListener('click', function() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
                this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        }
    }

    // Setup for Password and Confirm Password fields
    setupPasswordToggle('togglePassword1', 'passwordInput');
    setupPasswordToggle('togglePassword2', 'confirmPasswordInput');

    // Set fresh login flag when form is submitted
    document.querySelector('form').addEventListener('submit', function() {
        sessionStorage.setItem('fresh_login', 'true');
    });
    </script>
</body>

</html>