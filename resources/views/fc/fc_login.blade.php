@extends('fc.layouts.master')

@section('title', 'FC Login - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')

    <!-- Main Content Box -->
    <main class="flex-grow-1 d-flex align-items-center py-5" style="background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 50%, #e8f5e9 100%);">
        <div class="container py-4 py-md-5">
            <div class="row justify-content-center">
                <!-- Form Content -->
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-body p-4 p-md-5">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <!--display errors if any -->
                            @if ($errors->any())
                                <div class="alert alert-danger mb-3">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form class="row g-3 g-md-4" method="POST" action="{{ route('fc.login.verify') }}">
                                @csrf
                                <div class="text-center mb-3">
                                    <h1 class="h4 fw-bold mb-1" style="color: #004a93;">Login to Foundation Course</h1>
                                    <p class="text-muted small mb-0">Use the credentials shared with you to access the portal.</p>
                                </div>
                                <hr class="mt-3 mb-3">

                                <!-- Username -->
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small text-uppercase text-muted mb-1">User Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="material-icons fs-6 text-muted">person</i>
                                        </span>
                                        <input type="text" class="form-control form-control-lg border-start-0" placeholder="Enter your user name"
                                            name="reg_name" required>
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small text-uppercase text-muted mb-1">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="material-icons fs-6 text-muted">lock</i>
                                        </span>
                                        <input type="password" class="form-control form-control-lg border-start-0" placeholder="Enter password"
                                            name="reg_password" id="password" required>
                                        <button type="button" class="btn btn-outline-secondary d-flex align-items-center justify-content-center border-start-0"
                                            onclick="togglePassword('password', this)"
                                            aria-label="Toggle password visibility">
                                            <i class="material-icons menu-icon fs-5">visibility</i>
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-end mt-2">
                                        <a href="{{ route('fc.password.forgot') }}"
                                            class="link-primary small text-decoration-none">Forgot password?</a>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="col-12 mt-2">
                                    <button type="submit" class="btn btn-primary w-100 btn-lg"
                                        style="background-color: #004a93;border-color: #004a93;">Sign in</button>
                                </div>
                            </form>

                            <!-- OR divider -->
                            <div class="d-flex align-items-center my-3">
                                <hr class="flex-grow-1">
                                <span class="mx-2 text-muted small text-uppercase fw-semibold">Or</span>
                                <hr class="flex-grow-1">
                            </div>

                            <!-- DigiLocker login -->
                            <div>
                                <form method="POST" action="{{ route('fc.login.digilocker.redirect') }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn w-100 border-0 d-flex align-items-center justify-content-center py-2"
                                        style="background-color:#ffffff; border-radius: 0.75rem; box-shadow: 0 0 0 1px #d0d7de;">
                                        <img src="https://play-lh.googleusercontent.com/EqNJ0V0N0vzNKxdOl-Uz4OW5t8b4BhROYEKvQVqi1s1O_Ng2E_AobK1YB5hVFvpD5Yk" alt="DigiLocker"
                                            class="me-2" style="height: 28px;">
                                        <span class="fw-semibold" style="color:#004a93;">Login with DigiLocker</span>
                                    </button>
                                    <p class="text-muted small mt-2 mb-0 text-center">
                                        Secure login using your DigiLocker account.
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Toggle Password Visibility Script -->
    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "visibility_off";
            } else {
                input.type = "password";
                icon.textContent = "visibility";
            }
        }
    </script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('sweet_success'))
        <script>
            Swal.fire({
                title: 'Success!',
                text: '{{ session('sweet_success') }}',
                icon: 'success',
                confirmButtonColor: '#004a93',
                confirmButtonText: 'OK'
            });
        </script>
    @endif
    @if ($errors->any())
        <script>
            let errorMessages = '';
            @foreach ($errors->all() as $error)
                errorMessages += `{{ $error }}\n`;
            @endforeach

            Swal.fire({
                title: 'Validation Error',
                text: errorMessages.trim(),
                icon: 'error',
                confirmButtonColor: '#004a93',
                confirmButtonText: 'OK'
            });
        </script>
    @endif
@endpush
