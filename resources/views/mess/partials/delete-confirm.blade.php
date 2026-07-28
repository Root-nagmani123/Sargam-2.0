{{--
    Reusable mess delete-confirmation dialog + success toast.

    Usage on a listing page:
      1. Give each delete <form> the class `mess-delete-form` and (optionally)
         `data-confirm-title` / `data-confirm-message`; drop any native
         `onsubmit="return confirm(...)"`.
      2. @include('mess.partials.delete-confirm') once on the page.

    - The submit is intercepted and the branded "Delete?" dialog (image 1) is shown;
      "Yes, Delete" submits the form, "Cancel, Keep it" dismisses it.
    - Any flashed session('success') renders as the global green success toast
      (image 2) via SweetAlert2 + public/js/sargam-success-toast.js — so pages that
      include this partial should NOT also render their own inline success alert.

    Reuses the global `programme-confirm-*` design system (public/css/custom.css).
--}}
@once
<div class="modal fade programme-confirm-modal-root" id="messDeleteConfirmModal" tabindex="-1"
    aria-labelledby="messDeleteConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered programme-confirm-dialog">
        <div class="modal-content programme-confirm-modal border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div class="programme-confirm-icon programme-confirm-icon--danger mb-4" role="img" aria-hidden="true">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="programme-confirm-title h4 fw-bold mb-3 mess-delete-confirm-title" id="messDeleteConfirmTitle">Delete?</h2>
                <p class="programme-confirm-message programme-confirm-message--danger mb-4 mb-md-5" id="messDeleteConfirmMessage">
                    Are you sure you want to delete this record?
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-stretch programme-confirm-actions">
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn programme-confirm-cancel--danger" id="messDeleteConfirmCancel">
                        Cancel, Keep it
                    </button>
                    <button type="button" class="btn btn-lg rounded-3 programme-confirm-btn programme-confirm-ok--danger" id="messDeleteConfirmOk">
                        Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Mock uses a dark title (the design-system default is brand blue). */
    #messDeleteConfirmModal .mess-delete-confirm-title { color: #101828; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var modalEl = document.getElementById('messDeleteConfirmModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var titleEl = document.getElementById('messDeleteConfirmTitle');
    var messageEl = document.getElementById('messDeleteConfirmMessage');
    var pendingForm = null;

    // Intercept delete-form submits (capture phase, so it beats other handlers).
    document.addEventListener('submit', function (e) {
        var form = e.target && e.target.closest ? e.target.closest('form.mess-delete-form') : null;
        if (!form) return;
        e.preventDefault();
        e.stopImmediatePropagation();

        pendingForm = form;
        titleEl.textContent = form.getAttribute('data-confirm-title') || 'Delete?';
        messageEl.textContent = form.getAttribute('data-confirm-message') || 'Are you sure you want to delete this record?';
        modal.show();
    }, true);

    document.getElementById('messDeleteConfirmOk').addEventListener('click', function () {
        var form = pendingForm;
        pendingForm = null;
        modal.hide();
        if (form) {
            // Bypass the intercepted submit event to actually post the form.
            HTMLFormElement.prototype.submit.call(form);
        }
    });

    document.getElementById('messDeleteConfirmCancel').addEventListener('click', function () {
        pendingForm = null;
        modal.hide();
    });
})();
</script>
@endpush
@endonce

{{-- Flash success → global green toast (image 2). Kept outside @once because it
     is driven by this request's session, not the static dialog markup. --}}
@if(session('success'))
@push('scripts')
<script>
(function () {
    var message = @json(session('success'));
    function fire() {
        if (typeof window.Swal === 'undefined' || typeof window.Swal.fire !== 'function') {
            return window.setTimeout(fire, 100);
        }
        window.Swal.fire({ icon: 'success', title: 'Success', text: message });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fire);
    } else {
        fire();
    }
})();
</script>
@endpush
@endif
