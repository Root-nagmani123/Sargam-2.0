{{--
    "Leave without saving?" confirmation for the Mess entry modals.

    Cancel, the X, the backdrop and Esc all discarded a half-filled Selling
    Voucher without a word — and these forms are long: client, store, buyer, and
    a line-item table built one row at a time. Losing that to a stray click on
    the backdrop is the expensive kind of silent.

    Usage: put `data-mess-confirm-leave` on the modal element. Every dismissal
    route funnels through Bootstrap's `hide.bs.modal`, so one listener covers all
    four. A form that has not been touched still closes immediately — the prompt
    only appears when there is something to lose.

    Reuses the `programme-confirm-*` design system (public/css/custom.css), in its
    warning tone rather than the danger tone the delete dialog uses: leaving a
    form is recoverable, deleting a record is not.

    @see mess.partials.delete-confirm
--}}
@once
<div class="modal fade programme-confirm-modal-root" id="messLeaveConfirmModal" tabindex="-1"
    aria-labelledby="messLeaveConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered programme-confirm-dialog">
        <div class="modal-content programme-confirm-modal border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div class="programme-confirm-icon programme-confirm-icon--warning mb-4" role="img" aria-hidden="true">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="programme-confirm-title h4 fw-bold mb-3" id="messLeaveConfirmTitle">Leave without saving?</h2>
                <p class="programme-confirm-message mb-4 mb-md-5" id="messLeaveConfirmMessage">
                    This form has unsaved changes. If you leave now, they will be lost.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-stretch programme-confirm-actions">
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn programme-confirm-cancel--primary" id="messLeaveConfirmCancel">
                        Keep editing
                    </button>
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn programme-confirm-ok--primary" id="messLeaveConfirmOk">
                        Yes, leave
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Match the delete dialog's dark title rather than the design-system blue. */
    #messLeaveConfirmModal .programme-confirm-title { color: #101828; }
    /* This dialog sits on top of the form modal it is guarding. */
    #messLeaveConfirmModal { z-index: 1075; }
    .modal-backdrop.mess-leave-backdrop { z-index: 1070; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var confirmEl = document.getElementById('messLeaveConfirmModal');
    if (!confirmEl || typeof bootstrap === 'undefined') return;

    var confirmModal = bootstrap.Modal.getOrCreateInstance(confirmEl);
    var okBtn = document.getElementById('messLeaveConfirmOk');
    var cancelBtn = document.getElementById('messLeaveConfirmCancel');
    var onConfirm = null;

    // Bootstrap gives the newest backdrop the same z-index as the first one, so
    // without this the guard dialog renders behind the form it is guarding.
    confirmEl.addEventListener('shown.bs.modal', function () {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length) {
            backdrops[backdrops.length - 1].classList.add('mess-leave-backdrop');
        }
    });

    okBtn.addEventListener('click', function () {
        var fn = onConfirm;
        onConfirm = null;
        confirmModal.hide();
        if (typeof fn === 'function') fn();
    });

    cancelBtn.addEventListener('click', function () {
        onConfirm = null;
        confirmModal.hide();
    });

    /** Everything the user could have typed or chosen, as one comparable string. */
    function snapshot(modalEl) {
        var parts = [];
        modalEl.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.type === 'hidden' && el.name === '_token') return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                parts.push((el.name || el.id) + '=' + (el.checked ? '1' : '0'));
            } else {
                parts.push((el.name || el.id) + '=' + (el.value || ''));
            }
        });
        // Line items are added as rows, so their count is part of the state even
        // when every field in the new row is still blank.
        parts.push('rows=' + modalEl.querySelectorAll('tbody tr').length);
        return parts.join('|');
    }

    document.querySelectorAll('[data-mess-confirm-leave]').forEach(function (modalEl) {
        var clean = null;
        var allowClose = false;

        modalEl.addEventListener('show.bs.modal', function () {
            clean = null;
            allowClose = false;
        });

        modalEl.addEventListener('shown.bs.modal', function () {
            // Baseline after the modal settles, not the instant it appears: these
            // forms finish dressing themselves once open — Choices.js rewrites the
            // selects, the date defaults to today, and the line-item table gets its
            // first empty row. Snapshotting before that leaves every field looking
            // edited, and an untouched form would nag on the way out.
            setTimeout(function () { clean = snapshot(modalEl); }, 450);
        });

        // A real submit is not an abandonment.
        modalEl.addEventListener('submit', function () { allowClose = true; }, true);

        modalEl.addEventListener('hide.bs.modal', function (e) {
            if (allowClose || clean === null) return;
            if (snapshot(modalEl) === clean) return;

            e.preventDefault();
            onConfirm = function () {
                allowClose = true;
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            };
            confirmModal.show();
        });
    });
})();
</script>
@endpush
@endonce
