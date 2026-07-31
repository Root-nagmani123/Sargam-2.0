@extends('fc.layouts.master')

@section('title', 'FC Login - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="fc-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                    <div class="fc-card fc-auth-card fc-card--tricolor">
                        <div class="fc-card-body">
                            <div class="fc-auth-head">
                                <h1 class="fc-auth-title">Login to Foundation Course</h1>
                                <p class="fc-auth-sub">Sign in with the credentials issued by the Academy</p>
                            </div>

                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0 small ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form class="row g-3" method="POST" action="{{ route('fc.login.verify') }}"
                                autocomplete="off">
                                @csrf

                                <div class="col-12">
                                    <label for="reg_name" class="fc-label">
                                        <i class="bi bi-person-fill" aria-hidden="true"></i> User Name
                                    </label>
                                    <input type="text"
                                        class="form-control fc-input @error('reg_name') is-invalid @enderror"
                                        id="reg_name" name="reg_name" placeholder="Enter your User Name"
                                        value="{{ old('reg_name') }}" autocomplete="off" required>
                                </div>

                                <div class="col-12">
                                    <label for="password" class="fc-label">
                                        <i class="bi bi-lock-fill" aria-hidden="true"></i> Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control fc-input @error('reg_password') is-invalid @enderror"
                                            placeholder="Enter Password" name="reg_password" id="password"
                                            autocomplete="off" required>
                                        <button type="button" class="btn fc-pw-toggle" id="fcPwToggle"
                                            aria-label="Show password" aria-controls="password">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-end mt-2">
                                        <a href="{{ route('fc.password.forgot') }}"
                                            class="small link-primary link-offset-2">Forgot Password?</a>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary fc-btn-block">
                                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign In
                                    </button>

                                    <div class="fc-secure-note">
                                        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                        <span>Secure &amp; encrypted connection</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // SweetAlert takes a colour string, not a CSS variable — read the brand
        // token off the portal scope so this stays in step with sargam-app.css.
        var FC_BRAND = (getComputedStyle(document.body).getPropertyValue('--fc-primary') || '').trim() || '#004a93';
    </script>

    <script>
        (function () {
            var btn = document.getElementById('fcPwToggle');
            var input = document.getElementById('password');
            if (!btn || !input) return;

            btn.addEventListener('click', function () {
                var hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                var icon = this.querySelector('i');
                icon.classList.toggle('bi-eye', !hidden);
                icon.classList.toggle('bi-eye-slash', hidden);
                this.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
            });
        })();
    </script>

    @if (session('sweet_success'))
        <script>
            Swal.fire({
                title: 'Success!',
                text: @json(session('sweet_success')),
                icon: 'success',
                confirmButtonColor: FC_BRAND,
                confirmButtonText: 'OK'
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                title: 'Validation Error',
                text: @json(implode("\n", $errors->all())),
                icon: 'error',
                confirmButtonColor: FC_BRAND,
                confirmButtonText: 'OK'
            });
        </script>
    @endif
@endpush
