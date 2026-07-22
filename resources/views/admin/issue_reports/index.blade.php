@extends('admin.layouts.master')

@section('title', 'Reported Issues')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Issue status pills (reuse programme-dt design system spacing) ── */
    .issue-status-badge {
        display: inline-block;
        padding: .35em .75em;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1;
    }
    .issue-status-badge--open {
        color: #b45309;
        background: #fef3c7;
    }
    .issue-status-badge--progress {
        color: #1d4ed8;
        background: #dbeafe;
    }
    .issue-status-badge--resolved {
        color: #047857;
        background: #d1fae5;
    }
    .issue-status-badge--closed {
        color: #4b5563;
        background: #e5e7eb;
    }
    .issue-detail-label {
        font-size: .75rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }
    .issue-detail-value {
        font-size: .95rem;
        color: #111827;
        word-break: break-word;
    }
</style>
@endpush

@section('content')
<div class="container-fluid programme-index-page">
    <x-breadcrum title="Reported Issues" />

    <div id="status-msg" class="mb-3"></div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Filter issues by status">
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
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Reported issues from the dashboard</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div id="issueDtSearch" class="programme-dt-search"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="issueDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Issue details + status modal -->
<div class="modal fade" id="issueDetailModal" tabindex="-1" aria-labelledby="issueDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2 mb-0" id="issueDetailModalLabel">
                    <i class="bi bi-exclamation-diamond-fill text-white" aria-hidden="true"></i>
                    <span id="issueDetailRef" class="text-white">Issue details</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="issueDetailLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
                    <p class="mt-3 mb-0 text-body-secondary">Loading issue details…</p>
                </div>
                <div id="issueDetailBody" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="issue-detail-label">Reported By</div>
                            <div class="issue-detail-value" id="issueReporter">—</div>
                            <div class="small text-body-secondary" id="issueReporterContact"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="issue-detail-label">Reported On</div>
                            <div class="issue-detail-value" id="issueReportedOn">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="issue-detail-label">Module</div>
                            <div class="issue-detail-value" id="issueModule">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="issue-detail-label">Sub-Module</div>
                            <div class="issue-detail-value" id="issueSubModule">—</div>
                        </div>
                        <div class="col-12">
                            <div class="issue-detail-label">Description</div>
                            <div class="issue-detail-value" id="issueDescription" style="white-space: pre-wrap;">—</div>
                        </div>
                        <div class="col-md-6">
                            <div class="issue-detail-label">Page URL</div>
                            <div class="issue-detail-value"><a href="#" id="issuePageUrl" target="_blank" rel="noopener">—</a></div>
                        </div>
                        <div class="col-md-6">
                            <div class="issue-detail-label">Attachment</div>
                            <div class="issue-detail-value" id="issueAttachment">No attachment</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3 align-items-end">
                        <div class="col-sm-8">
                            <label for="issueStatusSelect" class="issue-detail-label d-block mb-1">Update Status</label>
                            <select class="form-select" id="issueStatusSelect">
                                @foreach ($statusLabels as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <button type="button" class="btn btn-primary w-100" id="issueStatusSaveBtn">Save Status</button>
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
        var currentIssueId = null;
        var detailModalEl = document.getElementById('issueDetailModal');
        var detailModal = detailModalEl ? bootstrap.Modal.getOrCreateInstance(detailModalEl) : null;

        function showMsg(type, text) {
            $('#status-msg').html(
                '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                text +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
            );
        }

        /* ── Relocate DataTables search + footer into the design-system slots ── */
        function enhanceIssueDtControls() {
            var $wrapper = $('#issue-reports-table_wrapper');
            if (!$wrapper.length) return;

            var $searchSlot = $('#issueDtSearch');
            var $footer = $('#issueDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input').addClass('form-control shadow-none')
                        .attr('placeholder', 'Search').attr('aria-label', 'Search issues');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) {
                updateIssueDtCount();
                return;
            }

            var $paginate = $wrapper.find('.dataTables_paginate').first();
            var $length = $wrapper.find('.dataTables_length').first();
            var $info = $wrapper.find('.dataTables_info').first();
            if (!$footer.length) return;

            var $pagCol = $('<div class="programme-dt-pagination"></div>');
            var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

            if ($paginate.length) {
                $paginate.find('.pagination').addClass('mb-0');
                $pagCol.append($paginate);
            }
            if ($length.length) {
                var $select = $length.find('select').addClass('form-select form-select-sm').detach();
                $length.find('label').empty()
                    .append(document.createTextNode('Showing '))
                    .append($select)
                    .append(document.createTextNode(' '));
                $countCol.append($length);
            }
            if ($info.length) {
                $info.addClass('mb-0');
                $countCol.append($info);
            }

            $footer.append($pagCol).append($countCol);
            $footer.data('dtReady', true);
        }

        function updateIssueDtCount() {
            if (!table) return;
            var info = table.page.info();
            var $info = $('#issueDtFooter .dataTables_info');
            if ($info.length && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable('#issue-reports-table')) return;
            table = $('#issue-reports-table').DataTable();

            // Send the active status filter with every ajax request.
            $('#issue-reports-table').on('preXhr.dt', function (e, settings, data) {
                data.status_filter = currentStatus;
            });

            $('#issue-reports-table').on('draw.dt', function () {
                var $wrapper = $('#issue-reports-table_wrapper');
                if ($wrapper.find('.dataTables_paginate').length && !$('#issueDtFooter .dataTables_paginate').length) {
                    $('#issueDtFooter').empty().data('dtReady', false);
                    enhanceIssueDtControls();
                }
                updateIssueDtCount();
            });

            enhanceIssueDtControls();
            updateIssueDtCount();
            setTimeout(function () { enhanceIssueDtControls(); updateIssueDtCount(); }, 300);

            // Status filter pills
            $('.programme-status-pill').on('click', function () {
                $('.programme-status-pill').removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
                $(this).addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');
                currentStatus = String($(this).data('status'));
                table.ajax.reload();
            });
        }, 100);

        /* ── View details ── */
        $(document).on('click', '.issue-view-btn', function () {
            var url = $(this).data('url');
            if (!detailModal) return;

            $('#issueDetailLoading').removeClass('d-none');
            $('#issueDetailBody').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: url,
                type: 'GET',
                success: function (res) {
                    if (!res.success) {
                        showMsg('danger', res.message || 'Could not load issue.');
                        detailModal.hide();
                        return;
                    }
                    var d = res.issue;
                    currentIssueId = d.id;

                    $('#issueDetailRef').text('Issue ' + d.reference);
                    $('#issueReporter').text(d.reporter || '—');
                    var contact = [];
                    if (d.reporter_email) contact.push(d.reporter_email);
                    if (d.reporter_phone) contact.push(d.reporter_phone);
                    $('#issueReporterContact').text(contact.join(' · '));
                    $('#issueReportedOn').text(d.reported_on || '—');
                    $('#issueModule').text(d.module_name || '—');
                    $('#issueSubModule').text(d.sub_module || '—');
                    $('#issueDescription').text(d.description || '—');

                    if (d.page_url) {
                        $('#issuePageUrl').text(d.page_url).attr('href', d.page_url).show();
                    } else {
                        $('#issuePageUrl').text('—').removeAttr('href');
                    }

                    if (d.attachment_url) {
                        $('#issueAttachment').html('<a href="' + d.attachment_url + '" target="_blank" rel="noopener" class="d-inline-flex align-items-center gap-1"><i class="bi bi-paperclip"></i> View / download</a>');
                    } else {
                        $('#issueAttachment').text('No attachment');
                    }

                    $('#issueStatusSelect').val(String(d.status));

                    $('#issueDetailLoading').addClass('d-none');
                    $('#issueDetailBody').removeClass('d-none');
                },
                error: function () {
                    showMsg('danger', 'Error loading issue details. Please try again.');
                    detailModal.hide();
                }
            });
        });

        /* ── Save status ── */
        $('#issueStatusSaveBtn').on('click', function () {
            if (!currentIssueId) return;
            var $btn = $(this);
            var newStatus = $('#issueStatusSelect').val();
            var url = '{{ route('admin.issue-reports.status', ['id' => '__ID__']) }}'.replace('__ID__', currentIssueId);

            $btn.prop('disabled', true).text('Saving…');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    status: newStatus
                },
                success: function (res) {
                    if (res.success) {
                        showMsg('success', res.message || 'Status updated.');
                        if (table) table.ajax.reload(null, false);
                        if (detailModal) detailModal.hide();
                    } else {
                        showMsg('danger', res.message || 'Could not update status.');
                    }
                },
                error: function () {
                    showMsg('danger', 'Status update failed. Please try again.');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Save Status');
                }
            });
        });
    });
</script>
@endpush
