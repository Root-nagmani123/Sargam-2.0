@extends('fc.layouts.master')

@section('title', 'Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <main style="flex: 1;">
        <div class="container mt-5">
            <div class="row">
                <div class="col-md-6 col-lg-6 offset-md-2 offset-lg-1 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger mb-3">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form class="row g-3" method="POST" action="{{ route('fc.password.reset') }}">
                                @csrf
                                <h3 class="text-center fw-bold" style="color: #004a93;">Begin Secure Password Reset </h3>
                                <small class="text-muted text-center d-block fw-bold">Please enter your Mobile Number and
                                    Web Auth Code to
                                    reset your password</small>
                                <hr>

                                <!-- Mobile No. -->
                                <div class="col-md-12">
                                    <label class="form-label">Mobile No.</label>
                                    <input type="number" class="form-control" placeholder="Enter your Mobile No."
                                        name="mobile_number" id="mobile_number" required>
                                </div>

                                <!-- Web Auth -->
                                <div class="col-md-12">
                                    <label class="form-label">Web Auth Code</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="web_auth" id="web_auth" required>
                                        <button type="button" class="btn btn-outline-success"
                                            id="verifyWebAuthBtn">Verify</button>
                                    </div>
                                </div>

                                <!-- Username (shown after verification) -->
                                <div class="col-md-12 d-none" id="usernameContainer">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" id="verified_username" disabled>
                                    <small class="text-muted d-block fw-bold">
                                        This is your login username. Please keep it safe for future use.
                                    </small>
                                </div>

                                <!-- New Password -->
                                <div class="col-md-12">
                                    <label class="form-label">Enter New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" placeholder="Enter New Password"
                                            name="new_password" id="new_password" required disabled>
                                        <button type="button" class="btn btn-primary"
                                            onclick="togglePassword('new_password', this)"
                                            style="background-color: #004a93; border-color: #004a93;">
                                            <i class="material-icons menu-icon me-3 fs-3">visibility</i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="col-md-12">
                                    <label class="form-label">Enter Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" placeholder="Enter Confirm Password"
                                            name="confirm_password" id="confirm_password" required disabled>
                                        <button type="button" class="btn btn-primary"
                                            onclick="togglePassword('confirm_password', this)"
                                            style="background-color: #004a93; border-color: #004a93;">
                                            <i class="material-icons menu-icon me-3 fs-3">visibility</i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled
                                        style="background-color: #004a93; border-color: #004a93;">Submit</button>
                                </div>
                            </form>

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

        document.getElementById('verifyWebAuthBtn').addEventListener('click', function() {
            const mobile = document.getElementById('mobile_number').value.trim();
            const auth = document.getElementById('web_auth').value.trim();

            if (!mobile || !auth) {
                // alert("Please enter both mobile number and Web Auth code.");
                Swal.fire({
                    title: 'Input Required',
                    text: 'Please enter both mobile number and Web Auth code.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            fetch('{{ route('fc.verify_web_auth') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mobile_number: mobile,
                        web_auth: auth
                    })
                })
                .then(res => {
                    if (!res.ok) throw new Error('Server error');
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('verified_username').value = data.user_name;
                        document.getElementById('usernameContainer').classList.remove('d-none');

                        document.getElementById('new_password').disabled = false;
                        document.getElementById('confirm_password').disabled = false;
                        document.getElementById('submitBtn').disabled = false;

                        // Make verified fields readonly
                        document.getElementById('mobile_number').readOnly = true;
                        document.getElementById('web_auth').readOnly = true;

                        // alert("Verification successful. Please proceed to set your new login password.");
                        Swal.fire({
                            title: 'Verification Successful',
                            text: 'Please proceed to set your new login password.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // alert("Invalid mobile number or Web Auth code.");
                        Swal.fire({
                            title: 'Verification Failed',
                            text: 'Invalid mobile number or Web Auth code.',
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        });
                    }
                })
                .catch(err => {
                    console.error('Verification error:', err);
                    Swal.fire({
                        title: 'Error',
                        text: 'Something went wrong. Please try again later.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        });
    </script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
