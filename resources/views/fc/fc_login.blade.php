@extends('fc.layouts.master')

@section('title', 'FC Login - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main id="content" class="flex-grow-1 py-4 py-md-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-11 col-lg-10 col-xl-9">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="row g-0">
                            {{-- Branded welcome panel — desktop only; stacks away on small screens --}}
                            <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center p-5"
                                style="background: linear-gradient(135deg, #004a93 0%, #0a6bb5 100%); color:#ffffff;">
                                <h2 class="fw-bold mb-3" style="color:#ffffff;">Welcome Back</h2>
                                <p class="mb-4" style="color:#ffffff; opacity:.85;">Foundation Course Portal — Lal Bahadur Shastri National
                                    Academy of Administration.</p>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-start mb-3" style="color:#ffffff;"><i class="bi bi-shield-lock-fill fs-5 me-3" style="color:#ffffff;"></i><span style="color:#ffffff;">Secure sign-in for registered candidates.</span></li>
                                    <li class="d-flex align-items-start mb-3" style="color:#ffffff;"><i class="bi bi-file-earmark-text-fill fs-5 me-3" style="color:#ffffff;"></i><span style="color:#ffffff;">Continue your registration and document submission.</span></li>
                                    <li class="d-flex align-items-start" style="color:#ffffff;"><i class="bi bi-headset fs-5 me-3" style="color:#ffffff;"></i><span style="color:#ffffff;">Need help? Visit the Contact page.</span></li>
                                </ul>
                            </div>
                            {{-- Form panel --}}
                            <div class="col-12 col-lg-6">
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

                            <form class="row g-3 g-md-4" method="POST" action="{{ route('fc.login.verify') }}" autocomplete="off" id="fcLoginForm">
                                {{-- ⚠️ TEMPORARY load-test only — revert with: git checkout resources/views/fc/fc_login.blade.php --}}
                                {{-- @csrf --}}


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
                                        value="{{ old('reg_name') }}" autocomplete="off" required>
                                </div>

                                <div class="col-12">
                                    <label for="password" class="form-label fw-semibold">Password</label>
                                    <div class="input-group input-group-lg">
                                        <input type="password"
                                            class="form-control rounded-start-3 @error('reg_password') is-invalid @enderror"
                                            placeholder="Enter Password" name="reg_password" id="password"
                                            style="font-size: 0.875rem;"
                                            autocomplete="off" required>
                                        <button type="button"
                                            class="btn btn-primary rounded-end-3 px-3 d-inline-flex align-items-center justify-content-center"
                                            style="background-color: #004a93; border-color: #004a93;"
                                            onclick="togglePassword('password', this)"
                                            aria-label="Show password">
                                            <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- Captcha (bot / brute-force mitigation) — server-verified via required|captcha --}}
                                <div class="col-12">
                                    <label class="form-label fw-semibold mb-2">Verification <span class="text-danger">*</span></label>
                                    <div class="bg-light border border-light rounded-3 p-3 text-center">
                                        <div class="d-flex flex-column align-items-center gap-3">
                                            <div class="d-flex flex-wrap align-items-center justify-content-center gap-2">
                                                <img src="{{ captcha_src() }}" alt="Captcha" id="captchaImage"
                                                    class="img-fluid border rounded-3 shadow-sm bg-white p-2" style="max-height: 52px;">
                                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                                    onclick="refreshCaptcha()">
                                                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh
                                                </button>
                                            </div>
                                            <div class="w-100" style="max-width: 280px;">
                                                <input type="text" name="captcha"
                                                    class="form-control form-control-sm text-center rounded-3 @error('captcha') is-invalid @enderror"
                                                    placeholder="Enter captcha code" autocomplete="off" required>
                                            </div>
                                        </div>
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

        function refreshCaptcha() {
            var img = document.getElementById('captchaImage');
            if (img) { img.src = '{{ captcha_src() }}' + '?' + Math.random(); }
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

    {{-- Encrypt the password in the browser before POST so it is not literal plaintext
         in an intercepting proxy. AES-256-CBC, mirrored server-side in
         FrontPageController::decryptLoginPassword(). CryptoJS is SELF-HOSTED (same
         origin), not a CDN — so it can't be blocked by a network/firewall, which lets
         the guard below block a plaintext send without risking a CDN-outage lockout. --}}
    <script src="{{ asset('js/crypto-js.min.js') }}"></script>
    <script>
        (function () {
            var ENC_KEY = @json(config('app.password_enc_key'));
            var form = document.getElementById('fcLoginForm');
            var pwd  = document.getElementById('password');
            if (!form || !pwd) { return; }

            form.addEventListener('submit', function (e) {
                // Not configured / nothing to send / already encrypted → submit as-is.
                // (No key = rollout not active, so never block — no lockout.)
                if (!ENC_KEY || pwd.dataset.enc === '1' || !pwd.value) {
                    return;
                }

                // Key IS configured → encryption is required. CryptoJS is self-hosted,
                // so a missing library means a broken deploy, not a blocked CDN: block
                // the submit rather than fall back to sending the password in plaintext.
                if (typeof CryptoJS === 'undefined' || !CryptoJS.AES) {
                    e.preventDefault();
                    pwd.classList.add('is-invalid');
                    alert('Secure login could not initialize. Please refresh the page (Ctrl+F5) and try again.');
                    return;
                }

                e.preventDefault();
                var key = CryptoJS.enc.Base64.parse(ENC_KEY);
                var iv  = CryptoJS.enc.Utf8.parse('1234567890123456');
                pwd.value = CryptoJS.AES.encrypt(pwd.value, key, {
                    iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7
                }).toString();
                pwd.dataset.enc = '1'; // guard against double-encryption on resubmit
                form.submit();
            });
        })();
    </script>
@endpush
