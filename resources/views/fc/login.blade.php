@extends('fc.layouts.master')

@section('title', 'Login - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="fc-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="fc-card fc-auth-card fc-card--tricolor">
                        <div class="fc-card-body">
                            <div class="fc-auth-head">
                                <h1 class="fc-auth-title">User Authentication</h1>
                                <p class="fc-auth-sub">
                                    Authenticate with your registered mobile number to begin registration.
                                </p>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0 small ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('registration.verify') }}" autocomplete="off">
                                @csrf

                                <div class="mb-3">
                                    <label for="reg_mobile" class="fc-label">
                                        <i class="bi bi-phone" aria-hidden="true"></i>
                                        Mobile No. <span class="fc-req" aria-hidden="true">*</span>
                                    </label>
                                    <input type="tel" inputmode="numeric" pattern="[0-9]*"
                                        class="form-control fc-input" id="reg_mobile" name="reg_mobile"
                                        placeholder="Enter your registered mobile number" autocomplete="tel"
                                        value="{{ old('reg_mobile') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_web_code" class="fc-label">
                                        <i class="bi bi-key" aria-hidden="true"></i>
                                        Web Code <span class="fc-req" aria-hidden="true">*</span>
                                    </label>
                                    <input type="text" class="form-control fc-input" id="reg_web_code"
                                        name="reg_web_code" placeholder="Enter Web Code"
                                        value="{{ old('reg_web_code') }}" required>
                                    <div class="form-text">
                                        Web Code is sent to your registered mobile number / email.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_otp" class="fc-label">
                                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                                        OTP <span class="fc-req" aria-hidden="true">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control fc-input" id="reg_otp" name="otp"
                                            placeholder="Enter OTP" maxlength="6" inputmode="numeric"
                                            autocomplete="one-time-code" required>
                                        <button type="button" class="btn btn-outline-primary" id="sendRegOtpBtn">
                                            Send OTP
                                        </button>
                                    </div>
                                    <div class="form-text">OTP is sent to your registered mobile number.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="captcha" class="fc-label">
                                        <i class="bi bi-patch-check" aria-hidden="true"></i>
                                        Verification <span class="fc-req" aria-hidden="true">*</span>
                                    </label>
                                    <div class="fc-captcha">
                                        <div class="fc-captcha-row">
                                            <img src="{{ captcha_src() }}" alt="Captcha challenge" id="captchaImage">
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                id="refreshCaptchaBtn">
                                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh
                                            </button>
                                        </div>
                                        <div class="fc-captcha-input">
                                            <input type="text" class="form-control fc-input text-center" id="captcha"
                                                name="captcha" placeholder="Enter captcha code" autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary fc-btn-block">
                                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Login
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

            var CAPTCHA_SRC = @json(captcha_src());

            var refreshBtn = document.getElementById('refreshCaptchaBtn');
            var captchaImg = document.getElementById('captchaImage');
            if (refreshBtn && captchaImg) {
                refreshBtn.addEventListener('click', function () {
                    captchaImg.src = CAPTCHA_SRC + '?' + Date.now();
                });
            }

            var otpBtn = document.getElementById('sendRegOtpBtn');
            if (!otpBtn) return;

            otpBtn.addEventListener('click', async function () {
                var mobile = (document.getElementById('reg_mobile').value || '').trim();
                var webCode = (document.getElementById('reg_web_code').value || '').trim();

                if (!mobile || !webCode) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required',
                        text: 'Enter mobile number and web code first.',
                        confirmButtonColor: FC_BRAND
                    });
                    return;
                }

                this.disabled = true;
                try {
                    var res = await fetch(@json(route('registration.send_otp')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ reg_mobile: mobile, reg_web_code: webCode })
                    });
                    var data = await res.json();
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? 'OTP Sent' : 'Failed',
                        text: data.message || 'Unable to send OTP.',
                        confirmButtonColor: FC_BRAND
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Unable to send OTP.',
                        confirmButtonColor: FC_BRAND
                    });
                } finally {
                    this.disabled = false;
                }
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
