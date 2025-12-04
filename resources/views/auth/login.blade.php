<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <!-- Core Css -->
    <link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
    <title>Login - Sargam | Lal Bahadur Shastri National Academy of Administration</title>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="{{asset('admin_assets/images/logos/favicon.ico')}}" alt="loader" class="lds-ripple img-fluid">
    </div>
    <div id="main-wrapper">
        <div class="position-relative overflow-hidden auth-bg min-vh-100 w-100 d-flex align-items-center justify-content-center"
            style="background-image: url(https://alumni.lbsnaa.gov.in/user_assets/images/login/login-bg.webp);">
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center w-100 my-5 my-xl-0">
                    <div class="col-md-4 d-flex flex-column justify-content-center">
                        <div class="card mb-0 bg-body auth-login m-auto w-100">
                            <div class="row justify-content-center py-4">
                                <div class="col-lg-11">
                                    <div class="card-body">
                                        <a href="{{ route('login') }}" class="text-nowrap logo-img d-block mb-4 w-100">
                                            <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" class="dark-logo"
                                                alt="Logo" width="180">
                                            <img src="{{asset('admin_assets/images/logos/logo.svg')}}" class="dark-logo"
                                                alt="Logo-Dark">
                                        </a>
                                        <h2 class="lh-base mb-4">Let's get you signed in</h2>
                                        @if(session('error'))
                                        <div class="alert alert-danger">
                                            {{ session('error') }}
                                        </div>
                                        @endif
                                        <form action="{{route('post_login')}}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="Username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="Username"
                                                    placeholder="Enter your username" name="username"
                                                    autocomplete="username">
                                            </div>
                                            <div class="mb-4">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <label for="exampleInputPassword1"
                                                        class="form-label">Password</label>
                                                    <a class="text-primary link-dark fs-2" href="#">Forgot
                                                        Password ?</a>
                                                </div>
                                                <input type="password" class="form-control" id="exampleInputPassword1"
                                                    placeholder="Enter your password" name="password">
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-4">
                                                <div class="form-check">
                                                    <input class="form-check-input primary" type="checkbox" value=""
                                                        id="flexCheckChecked" checked="">
                                                    <label class="form-check-label text-dark" for="flexCheckChecked">
                                                        Keep me logged in
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-dark w-100 py-8 mb-4 rounded-1">Sign
                                                In</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="dark-transparent sidebartoggler"></div>
    <!-- Import Js Files -->
    <script src="{{asset('admin_assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('admin_assets/libs/simplebar/dist/simplebar.min.js')}}"></script>
    <script src="{{asset('admin_assets/js/theme/app.init.js')}}"></script>
    <script src="{{asset('admin_assets/js/theme/theme.js')}}"></script>
    <script src="{{asset('admin_assets/js/theme/app.min.js')}}"></script>
    <!-- solar icons -->
    <script src="{{asset('admin_assets/css/iconify-icon.min.js')}}"></script>
</body>

</html>