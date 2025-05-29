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
            <div class="position-relative overflow-hidden auth-bg min-vh-100 w-100 d-flex align-items-center justify-content-center" style="background-image: url(admin_assets/images/backgrounds/login-bg.jpg);">
                <div class="d-flex align-items-center justify-content-center w-100">
                    <div class="row justify-content-center w-100 my-5 my-xl-0">
                        <div class="col-md-9 d-flex flex-column justify-content-center">
                            <div class="card mb-0 bg-body auth-login m-auto w-100">
                                <div class="row gx-0">
                                    <!-- ------------------------------------------------- -->
                                    <!-- Part 1 -->
                                    <!-- ------------------------------------------------- -->
                                    <div class="col-xl-6 border-end">
                                        <div class="row justify-content-center py-4">
                                            <div class="col-lg-11">
                                                <div class="card-body">
                                                    <a href="{{ route('login') }}" class="text-nowrap logo-img d-block mb-4 w-100">
                                                    <img src="{{asset('admin_assets/images/logos/logo.svg')}}" class="dark-logo" alt="Logo-Dark">
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
                                                            <label for="username" class="form-label">User Name</label>
                                                            <input type="text" class="form-control" id="username" placeholder="Enter User Name" aria-describedby="username" name="user_name">
                                                        </div>
                                                        <div class="mb-4">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <label for="exampleInputPassword1" class="form-label">Password</label>
                                                                <a class="text-primary link-dark fs-2" href="#">Forgot
                                                                Password ?</a>
                                                            </div>
                                                            <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Enter your password" name="password">
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                                            <div class="form-check">
                                                                <input class="form-check-input primary" type="checkbox" value="" id="flexCheckChecked" checked="">
                                                                <label class="form-check-label text-dark" for="flexCheckChecked">
                                                                Keep me logged in
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-dark w-100 py-8 mb-4 rounded-1">Sign In</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- ------------------------------------------------- -->
                                    <!-- Part 2 -->
                                    <!-- ------------------------------------------------- -->
                                    <div class="col-xl-6 d-none d-xl-block">
                                        <div class="row justify-content-center align-items-start h-100">
                                            <div class="col-lg-9">
                                                <div id="auth-login" class="carousel slide auth-carousel" data-bs-ride="carousel">
                                                    <div class="carousel-indicators">
                                                        <button type="button" data-bs-target="#auth-login" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                                        <button type="button" data-bs-target="#auth-login" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                                        <button type="button" data-bs-target="#auth-login" data-bs-slide-to="2" aria-label="Slide 3"></button>
                                                    </div>
                                                    <div class="carousel-inner">
                                                        <div class="carousel-item active">
                                                            <div class="d-flex align-items-center justify-content-center w-100 h-100 flex-column gap-9 text-center">
                                                                <img src="admin_assets/images/backgrounds/login-side.png" alt="login-side-img" width="300" class="img-fluid">
                                                                <h4 class="mb-0">Feature Rich 3D Charts</h4>
                                                                <p class="fs-12 mb-0">Donec justo tortor, malesuada vitae faucibus ac, tristique sit amet
                                                                    massa.
                                                                    Aliquam dignissim nec felis quis imperdiet.
                                                                </p>
                                                                <a href="javascript:void(0)" class="btn btn-primary rounded-1">Learn More</a>
                                                            </div>
                                                        </div>
                                                        <div class="carousel-item">
                                                            <div class="d-flex align-items-center justify-content-center w-100 h-100 flex-column gap-9 text-center">
                                                                <img src="admin_assets/images/backgrounds/login-side.png" alt="login-side-img" width="300" class="img-fluid">
                                                                <h4 class="mb-0">Feature Rich 2D Charts</h4>
                                                                <p class="fs-12 mb-0">Donec justo tortor, malesuada vitae faucibus ac, tristique sit amet
                                                                    massa.
                                                                    Aliquam dignissim nec felis quis imperdiet.
                                                                </p>
                                                                <a href="javascript:void(0)" class="btn btn-primary rounded-1">Learn More</a>
                                                            </div>
                                                        </div>
                                                        <div class="carousel-item">
                                                            <div class="d-flex align-items-center justify-content-center w-100 h-100 flex-column gap-9 text-center">
                                                                <img src="admin_assets/images/backgrounds/login-side.png" alt="login-side-img" width="300" class="img-fluid">
                                                                <h4 class="mb-0">Feature Rich 1D Charts</h4>
                                                                <p class="fs-12 mb-0">Donec justo tortor, malesuada vitae faucibus ac, tristique sit amet
                                                                    massa.
                                                                    Aliquam dignissim nec felis quis imperdiet.
                                                                </p>
                                                                <a href="javascript:void(0)" class="btn btn-primary rounded-1">Learn More</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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