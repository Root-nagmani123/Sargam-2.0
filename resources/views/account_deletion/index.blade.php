@extends('admin.layouts.master')
@section('title', 'Account Deletion')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    .ad-page h2 { font-size: 1.25rem; font-weight: 700; color: var(--ds-ink); }
    .ad-page h3, .ad-modal h3 { font-size: 1rem; font-weight: 500; color: var(--ds-ink); }
    .ad-page .ad-lead, .ad-modal .ad-lead, .ad-step p { font-size: .875rem; color: var(--ds-ink-muted); }
    .ad-modal .modal-title { font-size: 1.25rem; font-weight: 700; color: var(--ds-ink); }
    .ad-note ul { list-style: disc; }
    .ad-modal .ad-btn-cancel { background: var(--ds-surface); border: 1px solid var(--ds-line); color: var(--ds-ink); }
    .ad-modal .ad-btn-cancel:hover { background: var(--ds-surface-2); }
    .ad-note { padding: .75rem var(--ds-space-3); border-radius: var(--ds-radius-2); font-size: .875rem; line-height: 1.6; }
    .ad-note--info { background: rgba(var(--bs-primary-rgb), .1); color: var(--ds-primary); }
    .ad-note--warn { background: var(--bs-warning-bg-subtle, #fff6d7); color: var(--ds-ink); }
    .ad-note strong { font-weight: 500; display: block; }
    .ad-step-no { font-size: 1.25rem; font-weight: 700; color: var(--ds-primary); }
    .ad-modal .modal-dialog { max-width: 920px; }
    .ad-modal .form-label { font-size: .8125rem; margin-bottom: var(--ds-space-1); }
    .ad-modal .form-control[readonly] { background: var(--ds-surface-2); }
    .ad-otp-modal .modal-dialog { max-width: 400px; }
    .ad-otp-boxes input { width: 3.25rem; height: 2.5rem; text-align: center; font-size: 1.125rem; }
</style>
@endpush

@section('content')
<div class="container-fluid ad-page">
    <x-breadcrum title="Account Deletion – SARGAM 2.0" :items="[
        ['label' => 'Home', 'url' => url('/')],
        ['label' => 'Account settings'],
        ['label' => 'Account deletion'],
    ]" />

    <div class="ds-card">
        <div class="ds-card-body ds-stack-4">
            <div>
                <h2 class="mb-2">Request account deletion</h2>
                <p class="ad-lead mb-0">Submit a request to delete your SARGAM 2.0 account and eligible personal data. Every request is verified and reviewed by the authorised authority.</p>
            </div>

            <div class="ad-note ad-note--info">
                <strong>Approval is required before deletion</strong>
                Submitting a request does not delete your account immediately. Your login access remains active until the request is approved.
            </div>

            <div>
                <h3 class="mb-3">How the process works</h3>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
                    @foreach ([
                        ['Submit request', 'Enter your registered User ID, choose a reason and confirm your request.'],
                        ['Verify identity', 'Verify using an OTP sent to your registered email.'],
                        ['Administrative review', 'The Computer Section checks your status and associated official records.'],
                        ['Approval and processing', 'An authorised administrator decides with remarks. Eligible data is deleted after approval.'],
                    ] as $i => [$stepTitle, $stepText])
                        <div class="col ad-step">
                            <div class="ad-step-no mb-2">{{ sprintf('%02d', $i + 1) }}</div>
                            <h3 class="mb-2">{{ $stepTitle }}</h3>
                            <p class="mb-0">{{ $stepText }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ad-note ad-note--warn">
                <strong>Important information</strong>
                Official, academic and administrative records required under applicable rules will be retained. Sessions and access tokens are revoked after approval.
            </div>

            @if ($openRequest)
                <div class="alert alert-info mb-0" role="status">
                    Your request <strong>{{ $openRequest->code }}</strong>, submitted on {{ $openRequest->created_at->format('d/m/Y') }},
                    {{ $openRequest->status === \App\Models\AccountDeletionRequest::STATUS_VERIFIED
                        ? 'has been verified by the Computer Section and is awaiting final approval.'
                        : 'is awaiting review by the Computer Section.' }}
                </div>
            @else
                @if ($rejectedRequest)
                    <div class="alert alert-secondary mb-0" role="status">
                        Your previous request <strong>{{ $rejectedRequest->code }}</strong> was not approved.
                        Remarks: {{ $rejectedRequest->admin_remarks ?? $rejectedRequest->review_remarks }}
                    </div>
                @endif
                <div>
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#adRequestModal">
                        Request account deletion
                    </button>
                    <p class="small text-body-secondary mt-2 mb-0">You will receive a unique Request ID after identity verification.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@unless ($openRequest)
{{-- Step 1: request form --}}
<div class="modal fade ad-modal" id="adRequestModal" tabindex="-1" aria-labelledby="adRequestTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" id="adRequestForm" novalidate>
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="adRequestTitle">Request Account Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="ad-lead">Submit a request to delete your SARGAM 2.0 account and eligible personal data, subject to applicable government record-retention requirements.</p>

                <h3 class="mb-3">Your Account Details</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" for="adUserName">User ID / Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adUserName" name="user_name" value="{{ $account['user_name'] }}" maxlength="100" required autocomplete="username">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="adFullName">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adFullName" value="{{ $account['full_name'] }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="adEmail">Registered Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="adEmail" value="{{ $account['email'] }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="adMobile">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adMobile" value="{{ $account['mobile'] }}" readonly>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="adReason">Reason for Account Deletion <span class="text-danger">*</span></label>
                    <select class="form-select" id="adReason" name="reason" required data-searchable="true" data-placeholder="Select a reason">
                        <option value=""></option>
                        @foreach ($reasons as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="adComments">Additional Comments (Optional; required for Other)</label>
                    <textarea class="form-control" id="adComments" name="comments" rows="2" maxlength="500" placeholder="Enter your comments here…"></textarea>
                    <div class="invalid-feedback"></div>
                    <div class="form-text text-end"><span id="adCommentsCount">0</span> / 500</div>
                </div>

                <div class="ad-note ad-note--warn mb-3">
                    <strong>Please Note:</strong>
                    <ul class="mb-0 ps-3">
                        <li>Your request will be verified by the authorised authority (Computer Section, LBSNAA).</li>
                        <li>Your login access will be revoked after approval.</li>
                        <li>Certain official, academic and administrative records may be retained under applicable rules.</li>
                    </ul>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="adConfirm" name="confirm" value="1" required>
                    <label class="form-check-label" for="adConfirm">I have read and understood the above conditions and agree to proceed with the account deletion request. <span class="text-danger">*</span></label>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-lg ad-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-lg" id="adSubmitBtn">Submit deletion request</button>
            </div>
        </form>
    </div>
</div>

{{-- Step 2: email OTP --}}
<div class="modal fade ad-modal ad-otp-modal" id="adOtpModal" tabindex="-1" aria-labelledby="adOtpTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="adOtpForm" novalidate>
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="adOtpTitle">Verify your identity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="ad-lead mb-2">Enter the 6-digit OTP sent to your registered email to confirm your account deletion request.</p>
                <p class="fw-medium mb-3" id="adMaskedEmail"></p>

                <label class="form-label" id="adOtpLabel">Email OTP <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 ad-otp-boxes" role="group" aria-labelledby="adOtpLabel">
                    @for ($d = 1; $d <= 6; $d++)
                        <input type="text" class="form-control" inputmode="numeric" maxlength="1" aria-label="Digit {{ $d }}" @if ($d === 1) autocomplete="one-time-code" @else autocomplete="off" @endif>
                    @endfor
                </div>
                <div class="invalid-feedback d-block" id="adOtpError"></div>
                <div class="d-flex justify-content-between small text-body-secondary mt-2">
                    <span id="adOtpExpiry"></span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="adOtpResend" disabled></button>
                </div>

                <p class="ad-lead mt-4 mb-0">Your request is generated only after successful verification. Your account will not be deleted immediately.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-lg ad-btn-cancel" id="adOtpBack">Back</button>
                <button type="submit" class="btn btn-primary btn-lg" id="adVerifyBtn">Verify &amp; submit</button>
            </div>
        </form>
    </div>
</div>
@endunless
@endsection

@unless ($openRequest)
@push('scripts')
<script>
(function () {
    const otpUrl = @json(route('account-deletion.otp'));
    const verifyUrl = @json(route('account-deletion.verify'));
    const otherReason = @json($otherReason);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const form = document.getElementById('adRequestForm');
    const otpForm = document.getElementById('adOtpForm');
    const requestModal = bootstrap.Modal.getOrCreateInstance('#adRequestModal');
    const otpModal = bootstrap.Modal.getOrCreateInstance('#adOtpModal');
    const boxes = Array.from(otpForm.querySelectorAll('.ad-otp-boxes input'));
    const comments = document.getElementById('adComments');
    const requestEl = document.getElementById('adRequestModal');
    const otpEl = document.getElementById('adOtpModal');
    let timers = [];

    // Open the next modal only after the current one has finished hiding, so backdrops don't stack.
    function swap(fromModal, fromEl, toModal) {
        fromEl.addEventListener('hidden.bs.modal', () => toModal.show(), { once: true });
        fromModal.hide();
    }

    async function post(url, body) {
        let res;
        try {
            res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: body,
            });
        } catch (e) {
            return { ok: false, status: 0, json: { message: 'Could not reach the server. Check your connection and try again.' } };
        }
        let json = {};
        try { json = await res.json(); } catch (e) { /* non-JSON error page */ }
        return { ok: res.ok, status: res.status, json: json };
    }

    // Disable the button and show a spinner while waiting; returns a function that restores it.
    function busy(button, label) {
        const html = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + label;
        return () => { button.disabled = false; button.innerHTML = html; };
    }

    function clearErrors(scope) {
        scope.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        scope.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; });
    }

    function showFormErrors(errors) {
        Object.entries(errors).forEach(([name, messages]) => {
            const field = form.elements[name];
            if (!field) return;
            field.classList.add('is-invalid');
            const feedback = field.closest('.col-md-6, .mb-4, .form-check').querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = messages[0];
        });
    }

    function failMessage(r) {
        return (r.json && r.json.message) || 'Something went wrong. Please try again.';
    }

    function countdown(el, seconds, render, done) {
        let left = seconds;
        const tick = () => {
            render(el, left);
            if (left-- <= 0) { clearInterval(id); if (done) done(); }
        };
        const id = setInterval(tick, 1000);
        timers.push(id);
        tick();
    }

    const mmss = s => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');

    function startTimers(data) {
        timers.forEach(clearInterval);
        timers = [];
        const resend = document.getElementById('adOtpResend');
        resend.disabled = true;
        countdown(document.getElementById('adOtpExpiry'), data.expires_in,
            (el, s) => { el.textContent = s > 0 ? 'OTP expires in ' + mmss(s) : 'OTP expired'; });
        countdown(resend, data.resend_in,
            (el, s) => { el.textContent = 'Resend OTP in ' + mmss(s); },
            () => { resend.disabled = false; resend.textContent = 'Resend OTP'; });
    }

    async function requestOtp(button) {
        clearErrors(form);
        const done = busy(button, 'Sending OTP…');
        const r = await post(otpUrl, new FormData(form));
        done();

        if (r.status === 422) {
            showFormErrors(r.json.errors || {});
            if (otpEl.classList.contains('show')) swap(otpModal, otpEl, requestModal);
            return false;
        }
        if (!r.ok) {
            Swal.fire({ icon: 'error', title: 'Unable to send OTP', text: failMessage(r) });
            return false;
        }

        document.getElementById('adMaskedEmail').textContent = r.json.masked_email;
        startTimers(r.json);
        return true;
    }

    comments.addEventListener('input', () => {
        document.getElementById('adCommentsCount').textContent = comments.value.length;
    });
    // Select2 (data-searchable) fires a jQuery change event, not a native one.
    $('#adReason').on('change', function () {
        comments.required = this.value === otherReason;
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (await requestOtp(document.getElementById('adSubmitBtn'))) {
            boxes.forEach(b => { b.value = ''; });
            clearErrors(otpForm);
            swap(requestModal, requestEl, otpModal);
        }
    });

    document.getElementById('adOtpResend').addEventListener('click', function () { requestOtp(this); });

    document.getElementById('adOtpBack').addEventListener('click', () => swap(otpModal, otpEl, requestModal));

    otpEl.addEventListener('shown.bs.modal', () => boxes[0].focus());

    boxes.forEach((box, i) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(-1);
            if (box.value && boxes[i + 1]) boxes[i + 1].focus();
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && boxes[i - 1]) boxes[i - 1].focus();
        });
        box.addEventListener('paste', (e) => {
            const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            if (!digits) return;
            e.preventDefault();
            boxes.forEach((b, j) => { b.value = digits[j] || ''; });
            boxes[Math.min(digits.length, 5)].focus();
        });
    });

    otpForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors(otpForm);
        const otp = boxes.map(b => b.value).join('');
        const error = document.getElementById('adOtpError');
        if (otp.length !== 6) {
            error.textContent = 'Enter the 6-digit OTP.';
            return;
        }

        const done = busy(document.getElementById('adVerifyBtn'), 'Verifying…');
        const body = new FormData();
        body.append('otp', otp);
        const r = await post(verifyUrl, body);
        done();

        if (!r.ok) {
            error.textContent = (r.json.errors && r.json.errors.otp && r.json.errors.otp[0]) || failMessage(r);
            return;
        }

        timers.forEach(clearInterval);
        otpModal.hide();
        Swal.fire({ icon: 'success', title: 'Request submitted', text: r.json.message })
            .then(() => window.location.reload());
    });
})();
</script>
@endpush
@endunless
