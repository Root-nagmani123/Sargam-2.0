@extends('fc.layouts.master')

@section('title', 'FC Login - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main id="content" class="flex-grow-1 py-4 py-md-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                    <div class="card border-0 shadow-lg rounded-4">
                        <div class="card-body p-4 p-md-5">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger mb-4 rounded-3" role="alert">
                                    <ul class="mb-0 small ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form class="row g-3 g-md-4" method="POST" action="{{ route('fc.login.verify') }}">
                                @csrf

                                <div class="col-12 text-center">
                                    <h1 class="h4 fw-bold text-primary mb-0">Login to Foundation Course</h1>
                                </div>

                                <div class="col-12">
                                    <hr class="my-0 text-secondary opacity-25">
                                </div>

                                <div class="col-12">
                                    <label for="reg_name" class="form-label fw-semibold">User Name</label>
                                    <input type="text" class="form-control form-control-lg rounded-3 @error('reg_name') is-invalid @enderror"
                                        id="reg_name" name="reg_name" placeholder="Enter your User Name"
                                        value="{{ old('reg_name') }}" autocomplete="username" required>
                                </div>

                                <div class="col-12">
                                    <label for="password" class="form-label fw-semibold">Password</label>
                                    <div class="input-group input-group-lg">
                                        <input type="password"
                                            class="form-control rounded-start-3 @error('reg_password') is-invalid @enderror"
                                            placeholder="Enter Password" name="reg_password" id="password"
                                            autocomplete="current-password" required>
                                        <button type="button"
                                            class="btn btn-primary rounded-end-3 px-3 d-inline-flex align-items-center justify-content-center"
                                            style="background-color: #004a93; border-color: #004a93;"
                                            onclick="togglePassword('password', this)"
                                            aria-label="Show password">
                                            <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="form-text mt-2">
                                        <a href="{{ route('fc.password.forgot') }}"
                                            class="link-primary link-offset-2 link-underline-opacity-25 small">Forget Password</a>
                                    </div>
                                </div>

                                <div class="col-12 pt-1">
                                    <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold"
                                        style="background-color: #004a93; border-color: #004a93;">
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
                btn.setAttribute('aria-label', 'Show password');
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
