@extends('admin.layouts.master')

@section('title', 'Change Requests (HAC Approved) - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Change Requests (HAC Approved)"></x-breadcrum>
    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Change Requests (Approved by HAC)</h1>
                    <p class="text-muted small mb-0">View and manage change requests approved by HAC. Use Approve/Disapprove to take action on each request.</p>
                </div>
            </div>

            <div class="estate-change-request-table-wrapper">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0 estate-change-request-table',
                    'aria-describedby' => 'change-request-caption'
                ]) !!}
            </div>
            <div id="change-request-caption" class="visually-hidden">Change Requests approved by HAC</div>
        </div>
    </div>
</div>

{{-- Disapprove reason modal --}}
<div class="modal fade" id="disapproveChangeRequestModal" tabindex="-1" aria-labelledby="disapproveChangeRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="disapproveChangeRequestModalLabel">Reason for Disapproval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDisapproveChangeRequest" method="POST" action="">
                @csrf
                <div class="modal-body pt-2">
                    <p class="text-muted small mb-2">Request ID: <strong id="disapproveModalRequestId"></strong></p>
                    <label for="disapprove_reason" class="form-label">Reason / Remark <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="disapprove_reason" name="disapprove_reason" rows="4" maxlength="500" placeholder="Enter reason for disapproval..." required></textarea>
                    <div class="form-text">Max 500 characters. This remark will be saved and shown in the table.</div>
                    <div id="disapproveFormError" class="alert alert-danger mt-2 d-none" role="alert"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="btnSubmitDisapprove">Submit Disapproval</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    function wrapTableScroll() {
        var tbl = document.getElementById('estateChangeRequestTable');
        if (tbl && tbl.parentNode && !tbl.parentNode.classList.contains('table-scroll-only')) {
            var wrap = document.createElement('div');
            wrap.className = 'table-scroll-only';
            wrap.style.overflowX = 'auto';
            wrap.style.webkitOverflowScrolling = 'touch';
            tbl.parentNode.insertBefore(wrap, tbl);
            wrap.appendChild(tbl);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wrapTableScroll);
    } else {
        wrapTableScroll();
    }
    document.addEventListener('DOMContentLoaded', function() {
        var disapproveModalEl = document.getElementById('disapproveChangeRequestModal');
        var disapproveModal = disapproveModalEl ? new bootstrap.Modal(disapproveModalEl) : null;
        var form = document.getElementById('formDisapproveChangeRequest');
        var disapproveRequestIdSpan = document.getElementById('disapproveModalRequestId');
        var reasonTextarea = document.getElementById('disapprove_reason');
        var formErrorEl = document.getElementById('disapproveFormError');

        $(document).on('click', '.btn-disapprove-change-request', function() {
            var id = $(this).data('id');
            var requestId = $(this).data('request-id');
            if (!disapproveModal || !form) return;
            form.action = '{{ route("admin.estate.change-request.disapprove", ["id" => "__ID__"]) }}'.replace('__ID__', id);
            if (disapproveRequestIdSpan) disapproveRequestIdSpan.textContent = requestId || ('#' + id);
            if (reasonTextarea) { reasonTextarea.value = ''; reasonTextarea.removeAttribute('disabled'); }
            if (formErrorEl) { formErrorEl.classList.add('d-none'); formErrorEl.textContent = ''; }
            disapproveModal.show();
        });

        $(document).on('submit', 'form[data-confirm]', function(e) {
            if (!confirm($(this).data('confirm') || 'Are you sure?')) e.preventDefault();
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var submitBtn = document.getElementById('btnSubmitDisapprove');
                if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting...'; }
                if (formErrorEl) { formErrorEl.classList.add('d-none'); formErrorEl.textContent = ''; }

                var formData = new FormData(form);
                var actionUrl = form.getAttribute('action');

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
                .then(function(result) {
                    if (result.ok && result.data.success) {
                        disapproveModal.hide();
                        var dt = $('#estateChangeRequestTable').DataTable();
                        if (dt && dt.ajax) dt.ajax.reload(null, false);
                        var alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.setAttribute('role', 'alert');
                        alert.innerHTML = '<span>' + (result.data.message || 'Change request disapproved. Remark saved.') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        var cardBody = form.closest('.container-fluid').querySelector('.card-body');
var wrapper = cardBody && cardBody.querySelector('.estate-change-request-table-wrapper');
if (cardBody && wrapper) cardBody.insertBefore(alert, wrapper);
                    } else {
                        var msg = (result.data && result.data.message) || (result.data && result.data.errors && Object.values(result.data.errors).flat().join(' ')) || 'Something went wrong.';
                        if (formErrorEl) { formErrorEl.textContent = msg; formErrorEl.classList.remove('d-none'); }
                    }
                })
                .catch(function() {
                    if (formErrorEl) { formErrorEl.textContent = 'Network error. Please try again.'; formErrorEl.classList.remove('d-none'); }
                })
                .finally(function() {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Disapproval'; }
                });
            });
        }
    });
    </script>
@endpush
