@extends('admin.layouts.master')

@section('title', 'Change Password')

@section('content')
<div class="container-fluid px-3 px-lg-4 change-password-page pb-4">
    <x-breadcrum title="Change Password" />
    <x-session_message />

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex gap-3 align-items-start">
                <span class="material-icons material-symbols-rounded flex-shrink-0 text-danger" style="font-size:1.5rem;" aria-hidden="true">gpp_bad</span>
                <div class="flex-grow-1 min-w-0">
                    <strong class="d-block mb-2">We could not update your password</strong>
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center g-4 align-items-stretch">
        <div class="col-xl-7 col-lg-7">
            <div class="card rounded-4 border-0 shadow-sm overflow-hidden cp-card-main h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <div class="d-flex flex-wrap align-items-start gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white flex-shrink-0 shadow-sm"
                            style="width:3.25rem;height:3.25rem;background:linear-gradient(145deg,#004a93 0%,#0d6efd 55%,#0a58ca 100%);"
                            aria-hidden="true">
                            <span class="material-icons material-symbols-rounded" style="font-size:1.85rem;">lock_reset</span>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <h1 class="h5 mb-1 fw-semibold">Change password</h1>
                            <p class="small text-body-secondary mb-0 lh-base">For your account security, choose a unique password and do not share it with anyone.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 pt-3">
                    <form method="POST" action="{{ route('admin.password.submit_change_password') }}" id="changePasswordForm">
                        @csrf

                        <div class="mb-4">
                            <label for="current_password" class="form-label fw-semibold small mb-2">Current password</label>
                            <div class="input-group input-group-lg">
                                <input type="password"
                                    class="form-control rounded-start-3 @error('current_password') is-invalid @enderror"
                                    id="current_password"
                                    name="current_password"
                                    required
                                    autocomplete="current-password"
                                    aria-describedby="current-password-help">
                                <button type="button"
                                    class="btn btn-outline-secondary password-toggle-btn rounded-end-3 border-start-0"
                                    data-cp-toggle="current_password"
                                    aria-label="Show current password"
                                    aria-pressed="false">
                                    <span class="material-icons material-symbols-rounded cp-icon-show" style="font-size:1.25rem;">visibility</span>
                                    <span class="material-icons material-symbols-rounded cp-icon-hide d-none" style="font-size:1.25rem;">visibility_off</span>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <p class="form-text small mb-0 mt-2" id="current-password-help">Enter the password you use to sign in today.</p>
                        </div>

                        <div class="mb-2">
                            <label for="new_password" class="form-label fw-semibold small mb-2">New password</label>
                            <div class="input-group input-group-lg">
                                <input type="password"
                                    class="form-control rounded-start-3 @error('new_password') is-invalid @enderror"
                                    id="new_password"
                                    name="new_password"
                                    required
                                    autocomplete="new-password"
                                    aria-describedby="new-password-hint cp-strength-label">
                                <button type="button"
                                    class="btn btn-outline-secondary password-toggle-btn rounded-end-3 border-start-0"
                                    data-cp-toggle="new_password"
                                    aria-label="Show new password"
                                    aria-pressed="false">
                                    <span class="material-icons material-symbols-rounded cp-icon-show" style="font-size:1.25rem;">visibility</span>
                                    <span class="material-icons material-symbols-rounded cp-icon-hide d-none" style="font-size:1.25rem;">visibility_off</span>
                                </button>
                            </div>
                            @error('new_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 mb-1">
                                <span class="form-text small mb-0" id="new-password-hint">Strength is estimated in your browser only.</span>
                                <span class="small text-body-secondary fw-medium" id="cp-strength-label" aria-live="polite"></span>
                            </div>
                            <div class="cp-strength-bar mb-4" aria-hidden="true"><span id="cp-strength-fill"></span></div>
                        </div>

                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label fw-semibold small mb-2">Confirm new password</label>
                            <div class="input-group input-group-lg">
                                <input type="password"
                                    class="form-control rounded-start-3 @error('new_password_confirmation') is-invalid @enderror"
                                    id="new_password_confirmation"
                                    name="new_password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    aria-describedby="cp-match-msg">
                                <button type="button"
                                    class="btn btn-outline-secondary password-toggle-btn rounded-end-3 border-start-0"
                                    data-cp-toggle="new_password_confirmation"
                                    aria-label="Show confirmation password"
                                    aria-pressed="false">
                                    <span class="material-icons material-symbols-rounded cp-icon-show" style="font-size:1.25rem;">visibility</span>
                                    <span class="material-icons material-symbols-rounded cp-icon-hide d-none" style="font-size:1.25rem;">visibility_off</span>
                                </button>
                            </div>
                            @error('new_password_confirmation')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <p class="small mb-0 mt-2" id="cp-match-msg" style="min-height:1.35rem;" aria-live="polite"></p>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-3 align-items-stretch align-items-sm-center justify-content-between pt-2 border-top">
                            <a href="{{ route('admin.dashboard') }}"
                                class="btn btn-outline-secondary rounded-pill order-2 order-sm-1 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2">
                                <span class="material-icons material-symbols-rounded" style="font-size:1.1rem;">arrow_back</span>
                                Back to dashboard
                            </a>
                            <button type="submit" class="btn btn-submit-password rounded-pill order-1 order-sm-2 px-4 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2">
                                <span class="material-icons material-symbols-rounded" style="font-size:1.2rem;">verified_user</span>
                                Update password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-5 col-lg-5">
            <div class="card rounded-4 border-0 shadow-sm h-100 bg-body-tertiary">
                <div class="card-body p-4 d-flex flex-column">
                    <h2 class="h6 fw-semibold mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons material-symbols-rounded text-primary" style="font-size:1.35rem;">shield_lock</span>
                        Good habits
                    </h2>
                    <div class="d-flex flex-column gap-3 flex-grow-1">
                        <div class="cp-tip-item d-flex gap-3">
                            <span class="badge rounded-pill text-bg-primary align-self-start">1</span>
                            <p class="small text-body-secondary mb-0 lh-base">Use at least 12 characters if you can, mixing letters, numbers, and symbols.</p>
                        </div>
                        <div class="cp-tip-item d-flex gap-3">
                            <span class="badge rounded-pill text-bg-primary align-self-start">2</span>
                            <p class="small text-body-secondary mb-0 lh-base">Do not reuse passwords from email, banking, or other systems.</p>
                        </div>
                        <div class="cp-tip-item d-flex gap-3">
                            <span class="badge rounded-pill text-bg-primary align-self-start">3</span>
                            <p class="small text-body-secondary mb-0 lh-base">Use the eye buttons to verify spelling before you submit.</p>
                        </div>
                    </div>
                    <div class="rounded-3 bg-white bg-opacity-50 border border-primary border-opacity-10 p-3 mt-4">
                        <div class="d-flex gap-2 align-items-start">
                            <span class="material-icons material-symbols-rounded text-primary flex-shrink-0" style="font-size:1.25rem;">info</span>
                            <p class="small text-body-secondary mb-0 lh-base">Final rules are always enforced on the server; this page only helps you choose a safer password.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .change-password-page .cp-card-main {
        border-left: 4px solid #004a93;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    @media (prefers-reduced-motion: reduce) {
        .change-password-page .cp-card-main { transition: none; }
    }
    .change-password-page .cp-card-main:hover {
        box-shadow: 0 0.5rem 1.25rem rgba(0, 74, 147, 0.12) !important;
    }
    .change-password-page .password-toggle-btn {
        min-width: 3rem;
        border-color: var(--bs-border-color);
    }
    .change-password-page .password-toggle-btn:focus-visible {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        z-index: 4;
    }
    .change-password-page .btn-submit-password {
        background-color: #af2910;
        border-color: #af2910;
        color: #fff;
    }
    .change-password-page .btn-submit-password:hover,
    .change-password-page .btn-submit-password:focus {
        background-color: #8f2210;
        border-color: #8f2210;
        color: #fff;
    }
    .change-password-page .btn-submit-password:focus-visible {
        box-shadow: 0 0 0 0.2rem rgba(175, 41, 16, 0.35);
    }
    .change-password-page .cp-strength-bar {
        height: 0.28rem;
        border-radius: 50rem;
        background: var(--bs-border-color);
        overflow: hidden;
    }
    .change-password-page .cp-strength-bar > span {
        display: block;
        height: 100%;
        width: 0%;
        border-radius: inherit;
        transition: width 0.25s ease, background-color 0.25s ease;
    }
    @media (prefers-reduced-motion: reduce) {
        .change-password-page .cp-strength-bar > span { transition: none; }
    }
    .change-password-page .cp-strength-weak { background-color: var(--bs-danger); }
    .change-password-page .cp-strength-medium { background-color: var(--bs-warning); }
    .change-password-page .cp-strength-strong { background-color: var(--bs-success); }
    .change-password-page .cp-tip-item {
        padding: 0.65rem 0.85rem;
        border-radius: 0.75rem;
        background: rgba(var(--bs-primary-rgb), 0.06);
        border: 1px solid rgba(var(--bs-primary-rgb), 0.12);
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.change-password-page [data-cp-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-cp-toggle');
            var input = document.getElementById(id);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            var field = id.replace(/_/g, ' ');
            btn.setAttribute('aria-label', show ? 'Hide ' + field : 'Show ' + field);
            var showIcon = btn.querySelector('.cp-icon-show');
            var hideIcon = btn.querySelector('.cp-icon-hide');
            if (showIcon && hideIcon) {
                showIcon.classList.toggle('d-none', show);
                hideIcon.classList.toggle('d-none', !show);
            }
        });
    });

    var newPass = document.getElementById('new_password');
    var confirmPass = document.getElementById('new_password_confirmation');
    var fill = document.getElementById('cp-strength-fill');
    var label = document.getElementById('cp-strength-label');
    var matchMsg = document.getElementById('cp-match-msg');

    function strengthScore(val) {
        if (!val || val.length === 0) return { w: 0, cls: '', text: '' };
        var score = 0;
        if (val.length >= 8) score++;
        if (val.length >= 12) score++;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        var w, cls, text;
        if (score <= 2) { w = 33; cls = 'cp-strength-weak'; text = 'Weak'; }
        else if (score <= 4) { w = 66; cls = 'cp-strength-medium'; text = 'Medium'; }
        else { w = 100; cls = 'cp-strength-strong'; text = 'Strong'; }
        return { w: w, cls: cls, text: text };
    }

    function updateStrength() {
        if (!newPass || !fill || !label) return;
        var s = strengthScore(newPass.value);
        fill.style.width = s.w + '%';
        fill.className = s.cls || '';
        label.textContent = s.text;
    }

    function updateMatch() {
        if (!newPass || !confirmPass || !matchMsg) return;
        var a = newPass.value;
        var b = confirmPass.value;
        if (b.length === 0) {
            matchMsg.textContent = '';
            matchMsg.className = 'small mb-0 mt-2';
            matchMsg.style.minHeight = '1.35rem';
            return;
        }
        if (a === b) {
            matchMsg.textContent = 'Passwords match.';
            matchMsg.className = 'small mb-0 mt-2 text-success';
        } else {
            matchMsg.textContent = 'Passwords do not match yet.';
            matchMsg.className = 'small mb-0 mt-2 text-danger';
        }
    }

    if (newPass) {
        newPass.addEventListener('input', function () {
            updateStrength();
            updateMatch();
        });
    }
    if (confirmPass) {
        confirmPass.addEventListener('input', updateMatch);
    }
    updateStrength();
})();
</script>
@endpush
