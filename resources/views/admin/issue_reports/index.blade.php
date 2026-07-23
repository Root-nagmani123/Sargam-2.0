@extends('admin.layouts.master')
@section('title', 'Reported Issues')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* ── Status badges ── */
.issue-status-badge {
    display: inline-block;
    padding: .3em .75em;
    font-size: .75rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 4px;
}
.issue-status-badge--active  { color: #be123c; background: #ffe4e6; }
.issue-status-badge--fixed   { color: #15803d; background: #dcfce7; }

/* ── Tab buttons ── */
.issue-tab {
    display: inline-flex;
    align-items: center;
    padding: .45rem 1.15rem;
    font-size: .875rem;
    font-weight: 500;
    border-radius: 6px;
    border: 1.5px solid #d1d5db;
    background: #fff;
    color: #374151;
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
    white-space: nowrap;
    line-height: 1.4;
}
.issue-tab:hover   { background: #f1f5f9; border-color: #93afc8; }
.issue-tab.active  { background: #004a93; color: #fff; border-color: #004a93; }

/* ── Action buttons in table ── */
.issue-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .82rem;
    font-weight: 500;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    line-height: 1.4;
    transition: opacity .15s;
}
.issue-action-btn:hover         { opacity: .75; }
.issue-action-btn:disabled      { opacity: .45; cursor: not-allowed; }
.issue-mark-fix-btn             { color: #1d4ed8; }
.issue-delete-btn               { color: #dc2626; }

/* ── Attachment link ── */
.attachment-view {
    color: #2563eb;
    font-weight: 500;
    text-decoration: none;
}
.attachment-view:hover { text-decoration: underline; }

/* ── Table header ── */
#issue-reports-table thead th {
    font-size: .76rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6b7280;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}
#issue-reports-table tbody td { font-size: .875rem; }

/* ── Columns dropdown ── */
#columnsDropdown label { cursor: pointer; user-select: none; font-size: .875rem; }
#columnsDropdown input[type=checkbox] { accent-color: #004a93; }

/* ── Filter bar background ── */
.issue-filter-bar { background: #f9fafb; }

/* ── Print: hide chrome, show only table ── */
@media print {
    .no-print, .card-header-wrap, .issue-filter-bar,
    #issueDtFooter, .btn, nav { display: none !important; }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Reported Issues" />

    <div id="status-msg" class="mb-3"></div>

    <div class="card rounded-3 overflow-hidden shadow-sm border">

        {{-- ── Tab row ── --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3 bg-white border-bottom">
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter issues by status">
                <button class="issue-tab active" data-filter="all">All Issues</button>
                <button class="issue-tab"        data-filter="active">Active Issues</button>
                <button class="issue-tab"        data-filter="fixed">Fixed Issues</button>
            </div>
            <div class="d-flex gap-2 no-print">
                <a id="downloadBtn" href="{{ route('admin.issue-reports.export') }}"
                   class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-download" aria-hidden="true"></i> Download
                </a>
                <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                        onclick="window.print()">
                    <i class="bi bi-printer" aria-hidden="true"></i> Print
                </button>
            </div>
        </div>

        {{-- ── Filter bar ── --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 issue-filter-bar border-bottom no-print">
            <span class="fw-semibold text-secondary" style="font-size:.82rem;">Filter</span>

            <select id="deptFilter" class="form-select form-select-sm"
                    style="width:auto;min-width:145px;" aria-label="Filter by department">
                <option value="">Department</option>
            </select>

            <select id="submoduleFilter" class="form-select form-select-sm"
                    style="width:auto;min-width:145px;" aria-label="Filter by submodule">
                <option value="">Submodule</option>
            </select>

            <div class="d-flex align-items-center gap-1">
                <div class="input-group input-group-sm" style="width:155px;">
                    <span class="input-group-text bg-white px-2">
                        <i class="bi bi-calendar3" style="font-size:.8rem;"></i>
                    </span>
                    <input type="date" id="dateFrom" class="form-control form-control-sm border-start-0"
                           placeholder="From date" aria-label="From date">
                </div>
                <span class="text-body-secondary px-1">—</span>
                <div class="input-group input-group-sm" style="width:155px;">
                    <span class="input-group-text bg-white px-2">
                        <i class="bi bi-calendar3" style="font-size:.8rem;"></i>
                    </span>
                    <input type="date" id="dateTo" class="form-control form-control-sm border-start-0"
                           placeholder="To date" aria-label="To date">
                </div>
            </div>

            <button class="btn btn-sm btn-outline-danger" id="removeFilterBtn">Remove Filter</button>

            {{-- Columns visibility --}}
            <div class="dropdown ms-auto">
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Toggle columns">
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i> Columns
                </button>
                <ul class="dropdown-menu shadow-sm py-2" id="columnsDropdown" style="min-width:190px;"></ul>
            </div>

            {{-- Search toggle --}}
            <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center"
                    id="searchToggleBtn" aria-label="Toggle search" style="width:32px;height:32px;padding:0;">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>
            {{-- DataTable search will be moved here --}}
            <div id="issueDtSearch" class="d-none"></div>
        </div>

        {{-- ── DataTable ── --}}
        <div class="table-responsive">
            {!! $dataTable->table([
                'class'              => 'table table-hover align-middle mb-0 w-100',
                'data-sargam-dt-ui'  => 'false',
            ]) !!}
        </div>

        {{-- ── Footer: pagination + count ── --}}
        <div id="issueDtFooter"
             class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3 border-top bg-white no-print">
        </div>

    </div>
</div>

{{-- ── Delete confirmation modal ── --}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body p-4 text-center">
                <i class="bi bi-trash3 text-danger d-block mb-2" style="font-size:2rem;" aria-hidden="true"></i>
                <h6 class="fw-semibold mb-1">Delete Issue</h6>
                <p class="text-body-secondary small mb-4">This action cannot be undone.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-outline-secondary px-4"
                            data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-sm btn-danger px-4" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function () {
    var table            = null;
    var currentFilter    = 'all';
    var pendingDeleteUrl = null;
    var pendingDeleteId  = null;
    var deleteModal      = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

    /* ── Flash message ── */
    function showMsg(type, text) {
        $('#status-msg').html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            text +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
        );
        setTimeout(function () { $('#status-msg .alert').alert('close'); }, 4500);
    }

    /* ── Collect active filter state ── */
    function filterParams() {
        return {
            status_filter:    currentFilter,
            dept_filter:      $('#deptFilter').val()      || '',
            submodule_filter: $('#submoduleFilter').val() || '',
            date_from:        $('#dateFrom').val()        || '',
            date_to:          $('#dateTo').val()          || '',
        };
    }

    /* ── Keep Download link in sync with active filters ── */
    function updateDownloadLink() {
        var base = '{{ route('admin.issue-reports.export') }}';
        var p    = filterParams();
        var qs   = Object.entries(p)
            .filter(function (e) { return e[1] !== ''; })
            .map(function (e) { return encodeURIComponent(e[0]) + '=' + encodeURIComponent(e[1]); })
            .join('&');
        $('#downloadBtn').attr('href', base + (qs ? '?' + qs : ''));
    }

    /* ── Build footer: move paginate/length/info into #issueDtFooter ── */
    function buildFooter() {
        var $wrap   = $('#issue-reports-table_wrapper');
        var $footer = $('#issueDtFooter');
        if (!$wrap.length || $footer.data('built')) return;

        var $pag  = $wrap.find('.dataTables_paginate').first();
        var $len  = $wrap.find('.dataTables_length').first();
        var $info = $wrap.find('.dataTables_info').first();

        if (!$pag.length) return;

        var $left  = $('<div class="d-flex align-items-center gap-2 flex-wrap"></div>');
        var $right = $('<div class="d-flex align-items-center gap-3 ms-auto flex-wrap"></div>');

        if ($len.length) {
            var $sel = $len.find('select')
                .addClass('form-select form-select-sm')
                .css('width', 'auto');
            $len.empty()
                .append($('<span class="text-body-secondary small">Showing </span>'))
                .append($sel);
            $left.append($len);
        }
        if ($info.length) {
            $right.append($info.addClass('text-body-secondary small mb-0'));
        }
        if ($pag.length) {
            $pag.find('.pagination').addClass('mb-0');
            $right.append($pag);
        }

        $footer.append($left).append($right);
        $footer.data('built', true);
    }

    /* ── Build Columns dropdown checkboxes ── */
    function buildColumnsDropdown() {
        if (!table || $('#columnsDropdown').data('built')) return;
        var $menu = $('#columnsDropdown').empty();

        table.columns().every(function (idx) {
            var title = $(this.header()).text().trim();
            if (!title || title === 'Action') return;

            var visible = this.visible();
            var $li     = $('<li>');
            var $label  = $('<label class="dropdown-item py-1 mb-0">');
            var $cb     = $('<input type="checkbox" class="me-2">').prop('checked', visible).data('col', idx);
            $label.append($cb).append(title);
            $li.append($label);
            $menu.append($li);
        });

        $menu.on('change', 'input[type=checkbox]', function () {
            table.column($(this).data('col')).visible(this.checked);
        });

        $menu.data('built', true);
    }

    /* ── Move search input into the search slot ── */
    function buildSearch() {
        var $slot   = $('#issueDtSearch');
        var $wrap   = $('#issue-reports-table_wrapper');
        var $filter = $wrap.find('.dataTables_filter').first();
        if (!$filter.length || $slot.find('input').length) return;

        $filter.find('input')
            .addClass('form-control form-control-sm')
            .attr('placeholder', 'Search…')
            .css('width', '200px');
        $filter.find('label').contents()
            .filter(function () { return this.nodeType === 3; })
            .remove();

        $slot.append($filter);
    }

    /* ── Wait for DataTable to initialise ── */
    setTimeout(function () {
        if (!$.fn.DataTable.isDataTable('#issue-reports-table')) return;
        table = $('#issue-reports-table').DataTable();

        /* Inject filter params into every AJAX request */
        $('#issue-reports-table').on('preXhr.dt', function (e, settings, data) {
            $.extend(data, filterParams());
        });

        /* After each draw: ensure footer is built */
        $('#issue-reports-table').on('draw.dt', function () {
            if (!$('#issueDtFooter').data('built')) {
                buildFooter();
            }
        });

        buildFooter();
        buildColumnsDropdown();
        buildSearch();

        /* In case controls render slightly late */
        setTimeout(function () {
            buildFooter();
            buildColumnsDropdown();
            buildSearch();
        }, 350);
    }, 100);

    /* ── Status tab buttons ── */
    $(document).on('click', '.issue-tab', function () {
        $('.issue-tab').removeClass('active');
        $(this).addClass('active');
        currentFilter = String($(this).data('filter'));
        updateDownloadLink();
        if (table) table.ajax.reload();
    });

    /* ── Filter controls ── */
    $('#deptFilter, #submoduleFilter, #dateFrom, #dateTo').on('change', function () {
        updateDownloadLink();
        if (table) table.ajax.reload();
    });

    $('#removeFilterBtn').on('click', function () {
        $('#deptFilter').val('');
        $('#submoduleFilter').val('');
        $('#dateFrom').val('');
        $('#dateTo').val('');
        updateDownloadLink();
        if (table) table.ajax.reload();
    });

    /* ── Search toggle ── */
    $('#searchToggleBtn').on('click', function () {
        var $slot = $('#issueDtSearch');
        $slot.toggleClass('d-none');
        if (!$slot.hasClass('d-none')) {
            $slot.find('input').first().focus();
        }
    });

    /* ── Populate filter dropdowns ── */
    $.get('{{ route('admin.issue-reports.filter-options') }}', function (data) {
        var $dept = $('#deptFilter');
        var $sub  = $('#submoduleFilter');
        (data.departments || []).forEach(function (d) {
            $dept.append($('<option>').val(d).text(d));
        });
        (data.submodules || []).forEach(function (s) {
            $sub.append($('<option>').val(s).text(s));
        });
    });

    /* ── Mark as Fixed ── */
    $(document).on('click', '.issue-mark-fix-btn', function () {
        var $btn = $(this);
        var id   = $btn.data('id');
        var url  = $btn.data('url');
        $btn.prop('disabled', true);

        $.ajax({
            url: url, type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), status: {{ App\Models\IssueReport::STATUS_RESOLVED }} },
            success: function (res) {
                if (res.success) {
                    showMsg('success', 'Issue #' + id + ' marked as Fixed.');
                    if (table) table.ajax.reload(null, false);
                } else {
                    showMsg('danger', res.message || 'Could not update status.');
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showMsg('danger', 'Status update failed. Please try again.');
                $btn.prop('disabled', false);
            }
        });
    });

    /* ── Delete: open confirm modal ── */
    $(document).on('click', '.issue-delete-btn', function () {
        pendingDeleteId  = $(this).data('id');
        pendingDeleteUrl = $(this).data('url');
        deleteModal.show();
    });

    /* ── Delete: confirm ── */
    $('#confirmDeleteBtn').on('click', function () {
        if (!pendingDeleteUrl) return;
        var $btn = $(this);
        $btn.prop('disabled', true).text('Deleting…');

        $.ajax({
            url: pendingDeleteUrl, type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
            success: function (res) {
                if (res.success) {
                    showMsg('success', res.message || 'Issue deleted.');
                    if (table) table.ajax.reload(null, false);
                    deleteModal.hide();
                } else {
                    showMsg('danger', res.message || 'Could not delete issue.');
                }
            },
            error: function () {
                showMsg('danger', 'Delete failed. Please try again.');
            },
            complete: function () {
                $btn.prop('disabled', false).text('Delete');
                pendingDeleteId  = null;
                pendingDeleteUrl = null;
            }
        });
    });

    updateDownloadLink();
});
</script>
@endpush
