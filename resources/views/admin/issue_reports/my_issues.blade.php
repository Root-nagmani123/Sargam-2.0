@extends('admin.layouts.master')
@section('title', 'My Reported Issues')

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
.issue-status-badge--active { color: #be123c; background: #ffe4e6; }
.issue-status-badge--fixed  { color: #15803d; background: #dcfce7; }

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
.issue-tab:hover  { background: #f1f5f9; border-color: #93afc8; }
.issue-tab.active { background: #004a93; color: #fff; border-color: #004a93; }

/* ── Attachment link ── */
.attachment-view { color: #2563eb; font-weight: 500; text-decoration: none; }
.attachment-view:hover { text-decoration: underline; }

/* ── Table header ── */
#my-issue-reports-table thead th {
    font-size: .76rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6b7280;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}
#my-issue-reports-table tbody td { font-size: .875rem; }

/* ── Columns dropdown ── */
#myColumnsDropdown label { cursor: pointer; user-select: none; font-size: .875rem; }
#myColumnsDropdown input[type=checkbox] { accent-color: #004a93; }

/* ── Filter bar ── */
.issue-filter-bar { background: #f9fafb; }

@media print {
    .no-print, .issue-filter-bar, #myIssueDtFooter, .btn, nav { display: none !important; }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <x-breadcrum title="My Reported Issues" />

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
                <a id="myDownloadBtn" href="{{ route('my.issue-reports.export') }}"
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

            <select id="myDeptFilter" class="form-select form-select-sm"
                    style="width:auto;min-width:145px;" aria-label="Filter by department">
                <option value="">Department</option>
            </select>

            <select id="mySubmoduleFilter" class="form-select form-select-sm"
                    style="width:auto;min-width:145px;" aria-label="Filter by submodule">
                <option value="">Submodule</option>
            </select>

            <div class="d-flex align-items-center gap-1">
                <div class="input-group input-group-sm" style="width:155px;">
                    <span class="input-group-text bg-white px-2">
                        <i class="bi bi-calendar3" style="font-size:.8rem;"></i>
                    </span>
                    <input type="date" id="myDateFrom" class="form-control form-control-sm border-start-0"
                           aria-label="From date">
                </div>
                <span class="text-body-secondary px-1">—</span>
                <div class="input-group input-group-sm" style="width:155px;">
                    <span class="input-group-text bg-white px-2">
                        <i class="bi bi-calendar3" style="font-size:.8rem;"></i>
                    </span>
                    <input type="date" id="myDateTo" class="form-control form-control-sm border-start-0"
                           aria-label="To date">
                </div>
            </div>

            <button class="btn btn-sm btn-outline-danger" id="myRemoveFilterBtn">Remove Filter</button>

            {{-- Columns visibility --}}
            <div class="dropdown ms-auto">
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i> Columns
                </button>
                <ul class="dropdown-menu shadow-sm py-2" id="myColumnsDropdown" style="min-width:190px;"></ul>
            </div>

            {{-- Search toggle --}}
            <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center"
                    id="mySearchToggleBtn" aria-label="Toggle search" style="width:32px;height:32px;padding:0;">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>
            <div id="myIssueDtSearch" class="d-none"></div>
        </div>

        {{-- ── DataTable ── --}}
        <div class="table-responsive">
            {!! $dataTable->table([
                'class'             => 'table table-hover align-middle mb-0 w-100',
                'data-sargam-dt-ui' => 'false',
            ]) !!}
        </div>

        {{-- ── Footer ── --}}
        <div id="myIssueDtFooter"
             class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3 border-top bg-white no-print">
        </div>

    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function () {
    var table          = null;
    var currentFilter  = 'all';

    function showMsg(type, text) {
        $('#status-msg').html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            text +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
        );
        setTimeout(function () { $('#status-msg .alert').alert('close'); }, 4500);
    }

    function filterParams() {
        return {
            status_filter:    currentFilter,
            dept_filter:      $('#myDeptFilter').val()      || '',
            submodule_filter: $('#mySubmoduleFilter').val() || '',
            date_from:        $('#myDateFrom').val()        || '',
            date_to:          $('#myDateTo').val()          || '',
        };
    }

    function updateDownloadLink() {
        var base = '{{ route('my.issue-reports.export') }}';
        var p    = filterParams();
        var qs   = Object.entries(p)
            .filter(function (e) { return e[1] !== ''; })
            .map(function (e) { return encodeURIComponent(e[0]) + '=' + encodeURIComponent(e[1]); })
            .join('&');
        $('#myDownloadBtn').attr('href', base + (qs ? '?' + qs : ''));
    }

    function buildFooter() {
        var $wrap   = $('#my-issue-reports-table_wrapper');
        var $footer = $('#myIssueDtFooter');
        if (!$wrap.length || $footer.data('built')) return;

        var $pag  = $wrap.find('.dataTables_paginate').first();
        var $len  = $wrap.find('.dataTables_length').first();
        var $info = $wrap.find('.dataTables_info').first();
        if (!$pag.length) return;

        var $left  = $('<div class="d-flex align-items-center gap-2 flex-wrap"></div>');
        var $right = $('<div class="d-flex align-items-center gap-3 ms-auto flex-wrap"></div>');

        if ($len.length) {
            var $sel = $len.find('select').addClass('form-select form-select-sm').css('width', 'auto');
            $len.empty()
                .append($('<span class="text-body-secondary small">Showing </span>'))
                .append($sel);
            $left.append($len);
        }
        if ($info.length)  $right.append($info.addClass('text-body-secondary small mb-0'));
        if ($pag.length) { $pag.find('.pagination').addClass('mb-0'); $right.append($pag); }

        $footer.append($left).append($right);
        $footer.data('built', true);
    }

    function buildColumnsDropdown() {
        if (!table || $('#myColumnsDropdown').data('built')) return;
        var $menu = $('#myColumnsDropdown').empty();
        table.columns().every(function (idx) {
            var title = $(this.header()).text().trim();
            if (!title) return;
            var $li    = $('<li>');
            var $label = $('<label class="dropdown-item py-1 mb-0">');
            var $cb    = $('<input type="checkbox" class="me-2">').prop('checked', this.visible()).data('col', idx);
            $label.append($cb).append(title);
            $li.append($label);
            $menu.append($li);
        });
        $menu.on('change', 'input[type=checkbox]', function () {
            table.column($(this).data('col')).visible(this.checked);
        });
        $menu.data('built', true);
    }

    function buildSearch() {
        var $slot   = $('#myIssueDtSearch');
        var $wrap   = $('#my-issue-reports-table_wrapper');
        var $filter = $wrap.find('.dataTables_filter').first();
        if (!$filter.length || $slot.find('input').length) return;
        $filter.find('input')
            .addClass('form-control form-control-sm')
            .attr('placeholder', 'Search…')
            .css('width', '200px');
        $filter.find('label').contents()
            .filter(function () { return this.nodeType === 3; }).remove();
        $slot.append($filter);
    }

    /* ── Wait for DataTable ── */
    setTimeout(function () {
        if (!$.fn.DataTable.isDataTable('#my-issue-reports-table')) return;
        table = $('#my-issue-reports-table').DataTable();

        $('#my-issue-reports-table').on('preXhr.dt', function (e, settings, data) {
            $.extend(data, filterParams());
        });

        $('#my-issue-reports-table').on('draw.dt', function () {
            if (!$('#myIssueDtFooter').data('built')) buildFooter();
        });

        buildFooter();
        buildColumnsDropdown();
        buildSearch();
        setTimeout(function () { buildFooter(); buildColumnsDropdown(); buildSearch(); }, 350);
    }, 100);

    /* ── Tabs ── */
    $(document).on('click', '.issue-tab', function () {
        $('.issue-tab').removeClass('active');
        $(this).addClass('active');
        currentFilter = String($(this).data('filter'));
        updateDownloadLink();
        if (table) table.ajax.reload();
    });

    /* ── Filters ── */
    $('#myDeptFilter, #mySubmoduleFilter, #myDateFrom, #myDateTo').on('change', function () {
        updateDownloadLink();
        if (table) table.ajax.reload();
    });

    $('#myRemoveFilterBtn').on('click', function () {
        $('#myDeptFilter').val('');
        $('#mySubmoduleFilter').val('');
        $('#myDateFrom').val('');
        $('#myDateTo').val('');
        updateDownloadLink();
        if (table) table.ajax.reload();
    });

    /* ── Search toggle ── */
    $('#mySearchToggleBtn').on('click', function () {
        var $slot = $('#myIssueDtSearch');
        $slot.toggleClass('d-none');
        if (!$slot.hasClass('d-none')) $slot.find('input').first().focus();
    });

    /* ── Populate filter dropdowns (scoped to current user's data) ── */
    $.get('{{ route('my.issue-reports.filter-options') }}', function (data) {
        var $dept = $('#myDeptFilter');
        var $sub  = $('#mySubmoduleFilter');
        (data.departments || []).forEach(function (d) { $dept.append($('<option>').val(d).text(d)); });
        (data.submodules  || []).forEach(function (s) { $sub.append($('<option>').val(s).text(s)); });
    });

    updateDownloadLink();
});
</script>
@endpush
