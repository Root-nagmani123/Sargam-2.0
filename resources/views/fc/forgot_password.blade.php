@extends('fc.layouts.master')

@section('title', 'Reset Password - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="fc-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="fc-card fc-auth-card fc-card--tricolor">
                        <div class="fc-card-body">
                            <div class="fc-auth-head">
                                <h1 class="fc-auth-title">Begin Secure Password Reset</h1>
                                <p class="fc-auth-sub">
                                    Verify your identity with your registered mobile number and Web Auth Code,
                                    then set a new password.
                                </p>
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

                            <form method="POST" action="{{ route('fc.password.reset') }}" autocomplete="off">
                                @csrf

                                {{-- Step 1 — identity check --}}
                                <div class="ds-form-section fc-step" role="group"
                                    aria-labelledby="fc-step1-title">
                                    <h2 id="fc-step1-title" class="ds-form-section-title fc-step-title">
                                        <span class="fc-step-index" aria-hidden="true">1</span>
                                        Verify your identity
                                    </h2>

                                    <div class="mb-3">
                                        <label for="mobile_number" class="fc-label">
                                            <i class="bi bi-phone" aria-hidden="true"></i> Mobile No.
                                        </label>
                                        <input type="tel" inputmode="numeric" pattern="[0-9]*"
                                            class="form-control fc-input" placeholder="Enter your Mobile No."
                                            name="mobile_number" id="mobile_number" autocomplete="tel" required>
                                    </div>

                                    <div>
                                        <label for="web_auth" class="fc-label">
                                            <i class="bi bi-key" aria-hidden="true"></i> Web Auth Code
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control fc-input" name="web_auth"
                                                id="web_auth" autocomplete="one-time-code" required>
                                            <button type="button" class="btn btn-outline-primary" id="verifyWebAuthBtn">
                                                Verify
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            The Web Auth Code was emailed to you by the Academy.
                                        </div>
                                    </div>
                                </div>

                                {{-- Step 2 — unlocked by the Web Auth verification above --}}
                                <div class="ds-form-section fc-step is-locked" id="resetStep2" role="group"
                                    aria-labelledby="fc-step2-title">
                                    <h2 id="fc-step2-title" class="ds-form-section-title fc-step-title">
                                        <span class="fc-step-index" aria-hidden="true">2</span>
                                        Set a new password
                                        <span class="fc-step-hint">
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i> Locked
                                        </span>
                                    </h2>

                                    <div class="mb-3 d-none" id="usernameContainer">
                                        <label for="verified_username" class="fc-label">
                                            <i class="bi bi-person-badge" aria-hidden="true"></i> Username
                                        </label>
                                        <input type="text" class="form-control fc-input" id="verified_username" disabled>
                                        <div class="form-text">
                                            This is your login username. Please keep it safe for future use.
                                        </div>
                                    </div>

                                    <div class="mb-3 d-none" id="otpContainer">
                                        <label for="forgot_otp" class="fc-label">
                                            <i class="bi bi-shield-check" aria-hidden="true"></i> OTP
                                        </label>
                                        <input type="text" inputmode="numeric" class="form-control fc-input" name="otp"
                                            id="forgot_otp" placeholder="Enter OTP sent to your mobile"
                                            autocomplete="one-time-code" disabled>
                                        <div class="form-text">OTP is sent after Web Auth verification.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="fc-label">
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i> New Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control fc-input"
                                                placeholder="Enter New Password" name="new_password" id="new_password"
                                                autocomplete="new-password" required disabled>
                                            <button type="button" class="btn fc-pw-toggle" data-fc-toggle="new_password"
                                                aria-label="Show password" aria-controls="new_password">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="confirm_password" class="fc-label">
                                            <i class="bi bi-lock-fill" aria-hidden="true"></i> Confirm Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control fc-input"
                                                placeholder="Enter Confirm Password" name="confirm_password"
                                                id="confirm_password" autocomplete="new-password" required disabled>
                                            <button type="button" class="btn fc-pw-toggle"
                                                data-fc-toggle="confirm_password" aria-label="Show password"
                                                aria-controls="confirm_password">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary fc-btn-block" id="submitBtn" disabled>
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i> Reset Password
                                </button>

                                <div class="fc-secure-note">
                                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                    <span>Secure &amp; encrypted connection</span>
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
            'use strict';

            // ── Reveal-password buttons (delegated; both fields share one handler) ──
            document.querySelectorAll('[data-fc-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(this.dataset.fcToggle);
                    if (!input) return;
                    var hidden = input.type === 'password';
                    input.type = hidden ? 'text' : 'password';
                    var icon = this.querySelector('i');
                    icon.classList.toggle('bi-eye', !hidden);
                    icon.classList.toggle('bi-eye-slash', hidden);
                    this.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
                });
            });

            // ── Step 1: verify Web Auth, which unlocks step 2 ──
            var verifyBtn = document.getElementById('verifyWebAuthBtn');
            if (!verifyBtn) return;

            verifyBtn.addEventListener('click', function () {
                var mobile = document.getElementById('mobile_number').value.trim();
                var auth = document.getElementById('web_auth').value.trim();

                if (!mobile || !auth) {
                    Swal.fire({
                        title: 'Input Required',
                        text: 'Please enter both mobile number and Web Auth code.',
                        icon: 'warning',
                        confirmButtonColor: FC_BRAND,
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                fetch(@json(route('fc.verify_web_auth')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token())
                        },
                        body: JSON.stringify({ mobile_number: mobile, web_auth: auth })
                    })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Server error');
                        return res.json();
                    })
                    .then(function (data) {
                        if (!data.success) {
                            Swal.fire({
                                title: 'Verification Failed',
                                text: 'Invalid mobile number or Web Auth code.',
                                icon: 'error',
                                confirmButtonColor: FC_BRAND,
                                confirmButtonText: 'Try Again'
                            });
                            return;
                        }

                        document.getElementById('verified_username').value = data.user_name;
                        document.getElementById('usernameContainer').classList.remove('d-none');

                        document.getElementById('otpContainer').classList.remove('d-none');
                        var otp = document.getElementById('forgot_otp');
                        otp.disabled = false;
                        otp.required = true;

                        document.getElementById('new_password').disabled = false;
                        document.getElementById('confirm_password').disabled = false;
                        document.getElementById('submitBtn').disabled = false;

                        // Step 2 is now live — drop the dimmed/locked treatment.
                        document.getElementById('resetStep2').classList.remove('is-locked');

                        // Freeze the verified identity fields.
                        document.getElementById('mobile_number').readOnly = true;
                        document.getElementById('web_auth').readOnly = true;
                        verifyBtn.disabled = true;

                        Swal.fire({
                            title: 'Verification Successful',
                            text: data.message || 'OTP sent. Enter OTP and set your new password.',
                            icon: 'success',
                            confirmButtonColor: FC_BRAND,
                            confirmButtonText: 'OK'
                        }).then(function () {
                            otp.focus();
                        });
                    })
                    .catch(function (err) {
                        console.error('Verification error:', err);
                        Swal.fire({
                            title: 'Error',
                            text: 'Something went wrong. Please try again later.',
                            icon: 'error',
                            confirmButtonColor: FC_BRAND,
                            confirmButtonText: 'OK'
                        });
                    });
            });
        })();
    </script>

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
