@extends('layouts.app')

@section('content')
<div class="row m-0">
    <div class="col-12 p-0">
        <div class="login-card login-dark"
            style="background-image:url({{asset('admin_assets/images/login/login_bg.jpg')}}); background-size:cover; background-position:center; background-repeat:no-repeat; height:98vh; overflow-y:hidden;">
            <div>
                <!-- <div><a class="logo" href="index.html"><img class="img-fluid" src="{{asset('admin_assets/images/logo/logo.png')}}"
                            alt="looginpage" width="300"></a></div> -->
                <div><a class="logo" href="{{ route('login') }}"><img class="img-fluid" src="{{asset('admin_assets/images/logo/logo_sargam.png')}}"
                            alt="looginpage" width="300"></a></div>
                <div class="login-main">
                    <form class="theme-form" method="POST" action="{{ route('post_login') }}">
                        @csrf
                        <h4>Sign in to account </h4>
                        <p>Enter your email & password to login</p>
                        <div class="form-group">
                            <label class="col-form-label">Email Address</label>
                            <input class="form-control" type="email" required="" name="email" value="{{ old('email') }}" placeholder="Test@gmail.com">
                        </div>

                        <div class="form-group">
                            <label class="col-form-label">Password </label>
                            <div class="form-input position-relative">
                                <input class="form-control" type="password" required="" placeholder="*********" name="password">
                                <div class="show-hide"> <span class="show"></span></div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <div class="checkbox p-0">
                                <input id="checkbox1" type="checkbox">
                                <label class="text-muted" for="checkbox1">Remember password</label>
                            </div><a class="link" href="forget-password.html">Forgot password?</a>
                            <div class="text-end mt-3">
                                <button class="btn btn-primary btn-block w-100" type="submit">Sign in</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection