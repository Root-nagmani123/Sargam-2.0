@extends('admin.layouts.master')

@section('title', 'My Complaints')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* Same palette as the admin console's issue-status-badge, under its own prefix. */
    .complaint-status-badge {
        display: inline-block;
        padding: .35em .75em;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1;
    }
    .complaint-status-badge--open     { color: #b45309; background: #fef3c7; }
    .complaint-status-badge--progress { color: #1d4ed8; background: #dbeafe; }
    .complaint-status-badge--resolved { color: #047857; background: #d1fae5; }
    .complaint-status-badge--closed   { color: #4b5563; background: #e5e7eb; }
    .complaint-detail-label {
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }
    .complaint-detail-value {
        font-size: .95rem;
        color: #111827;
        word-break: break-word;
    }
</style>
@endpush

@section('content')
<div class="container-fluid programme-index-page">
    <x-breadcrum title="My Complaints" />

    <div id="status-msg" class="mb-3"></div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Filter complaints by status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    data-status="all" aria-pressed="true" aria-current="true">All</button>
            </li>
            @foreach ($statusLabels as $code => $label)
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        data-status="{{ $code }}" aria-pressed="false">{{ $label }}</button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- datatable-global-ui.js fills the search / pagination / count slots this
                 component renders, so no per-page DataTables chrome JS is needed. --}}
            <x-datatable-chrome table-id="my-complaints-table">
                <x-slot:toolbar>
                    <span class="programme-dt-filters-label">Issues you have reported</span>
                </x-slot:toolbar>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
            </x-datatable-chrome>
        </div>
    </div>
</div>

{{-- Read-only detail modal: reporters can see their complaint and its status but
     cannot change it. Status changes belong to the admin console. --}}
<div class="modal fade" id="complaintDetailModal" tabindex="-1" aria-labelledby="complaintDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2 mb-0" id="complaintDetailModalLabel">
                    <i class="bi bi-exclamation-diamond-fill text-white" aria-hidden="true"></i>
                    <span id="complaintDetailRef" class="text-white">Complaint details</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="complaintDetailLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
                    <p class="mt-3 mb-0 text-body-secondary">Loading complaint details…</p>
                </div>
                <div id="complaintDetailBody" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="complaint-detail-label">Reported On</div>
                            <div class="complaint-detail-value" id="complaintReportedOn">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="complaint-detail-label">Status</div>
                            <div class="complaint-detail-value" id="complaintStatus">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="complaint-detail-label">Module</div>
                            <div class="complaint-detail-value" id="complaintModule">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="complaint-detail-label">Sub-Module</div>
                            <div class="complaint-detail-value" id="complaintSubModule">—</div>
                        </div>
                        <div class="col-12">
                            <div class="complaint-detail-label">Description</div>
                            <div class="complaint-detail-value" id="complaintDescription" style="white-space: pre-wrap;">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="complaint-detail-label">Page URL</div>
                            <div class="complaint-detail-value"><a href="#" id="complaintPageUrl" target="_blank" rel="noopener">—</a></div>
                        </div>
                        <div class="col-md-6">
                            <div class="complaint-detail-label">Attachment</div>
                            <div class="complaint-detail-value" id="complaintAttachment">No attachment</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-body-tertiary px-4 py-3">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(document).ready(function () {
        var table = null;
        var currentStatus = 'all';
        var detailModalEl = document.getElementById('complaintDetailModal');
        var detailModal = detailModalEl ? bootstrap.Modal.getOrCreateInstance(detailModalEl) : null;

        var STATUS_CLASS = {
            0: 'complaint-status-badge--open',
            1: 'complaint-status-badge--progress',
            2: 'complaint-status-badge--resolved',
            3: 'complaint-status-badge--closed'
        };

        function showMsg(type, text) {
            $('#status-msg').html(
                '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                text +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
            );
        }

        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable('#my-complaints-table')) return;
            table = $('#my-complaints-table').DataTable();

            // Send the active status filter with every ajax request.
            $('#my-complaints-table').on('preXhr.dt', function (e, settings, data) {
                data.status_filter = currentStatus;
            });

            $('.programme-status-pill').on('click', function () {
                $('.programme-status-pill').removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
                $(this).addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');
                currentStatus = String($(this).data('status'));
                table.ajax.reload();
            });
        }, 100);

        $(document).on('click', '.complaint-view-btn', function () {
            var url = $(this).data('url');
            if (!detailModal) return;

            $('#complaintDetailLoading').removeClass('d-none');
            $('#complaintDetailBody').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: url,
                type: 'GET',
                success: function (res) {
                    if (!res.success) {
                        showMsg('danger', res.message || 'Could not load complaint.');
                        detailModal.hide();
                        return;
                    }
                    var d = res.issue;

                    $('#complaintDetailRef').text('Complaint ' + d.reference);
                    $('#complaintReportedOn').text(d.reported_on || '—');
                    $('#complaintStatus').html(
                        '<span class="badge rounded-1 complaint-status-badge ' +
                        (STATUS_CLASS[d.status] || STATUS_CLASS[0]) + '">' +
                        $('<div>').text(d.status_label || 'Open').html() + '</span>'
                    );
                    $('#complaintModule').text(d.module_name || '—');
                    $('#complaintSubModule').text(d.sub_module || '—');
                    $('#complaintDescription').text(d.description || '—');

                    if (d.page_url) {
                        $('#complaintPageUrl').text(d.page_url).attr('href', d.page_url).show();
                    } else {
                        $('#complaintPageUrl').text('—').removeAttr('href');
                    }

                    if (d.attachment_url) {
                        $('#complaintAttachment').html('<a href="' + d.attachment_url + '" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-1"><i class="bi bi-paperclip"></i> View / download</a>');
                    } else {
                        $('#complaintAttachment').text('No attachment');
                    }

                    $('#complaintDetailLoading').addClass('d-none');
                    $('#complaintDetailBody').removeClass('d-none');
                },
                error: function () {
                    showMsg('danger', 'Error loading complaint details. Please try again.');
                    detailModal.hide();
                }
            });
        });
    });
</script>
@endpush
