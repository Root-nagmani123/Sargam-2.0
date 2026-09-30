{{-- COE question papers — Confirm Freeze with OTP. Shared by every coe-* grid;
     behaviour is CoeGrid.freeze() in public/js/coe-grid.js.
     Params: $phoneMasked (required), $digits (OTP length, default 5). --}}
<div class="modal fade" id="qpFreezeModal" tabindex="-1" aria-labelledby="qpFreezeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content coe-modal border-0 shadow">
            <form class="coe-confirm" id="qpFreezeForm" novalidate>
                <div class="coe-confirm__icon coe-confirm__icon--freeze" aria-hidden="true">
                    <i class="bi bi-snow"></i>
                </div>
                <h5 class="coe-confirm__title" id="qpFreezeModalLabel">Confirm Freeze?</h5>
                <p class="coe-confirm__text">Are you sure you want to freeze this record?</p>
                <p class="coe-confirm__hint">
                    For freezing the OTP is sent to your Phone number {{ $phoneMasked }}.
                </p>

                <div class="coe-otp" role="group" aria-label="One-time password">
                    @for ($i = 0; $i < ($digits ?? 5); $i++)
                        <input type="text" class="coe-otp__box" inputmode="numeric" pattern="[0-9]*"
                               maxlength="1" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                               aria-label="OTP digit {{ $i + 1 }}">
                    @endfor
                </div>

                <div class="coe-otp-meta">
                    <button type="button" class="coe-otp-resend" id="qpOtpResend">Resend OTP</button>
                    <span class="coe-otp-wait d-none" id="qpOtpWait">
                        Resent! Wait for Resend again <strong id="qpOtpTimer">01:00</strong>
                    </span>
                </div>
                <p class="coe-otp-error d-none" id="qpOtpError" role="alert"></p>

                <div class="coe-confirm__actions">
                    <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary">Confirm Verify</button>
                </div>
            </form>
        </div>
    </div>
</div>
