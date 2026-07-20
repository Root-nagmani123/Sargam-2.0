@extends('admin.layouts.master')
@section('title', 'Request For Duplicate ID Card - Sargam')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* --- "Time Period" dual-month range calendar --- */
.idcp-toggle {
    height: 40px; min-width: 170px;
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0 0.875rem; font-size: 0.9375rem; font-weight: 400;
    color: #344054; background: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.idcp-toggle:hover { border-color: #004a93; color: #344054; }
.idcp-toggle.dropdown-toggle::after { margin-left: auto; color: #667085; }
.idcp-menu { min-width: auto; }
.idcp-cal { padding: var(--ds-space-3, 1rem); }
.idcp-cal-months { display: flex; gap: var(--ds-space-4, 1.5rem); }
@media (max-width: 575.98px) { .idcp-cal-months { flex-direction: column; gap: var(--ds-space-3, 1rem); } }
.idcp-cal-month { width: 232px; }
.idcp-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--ds-space-2, 0.5rem); }
.idcp-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink, #1f2937); }
.idcp-cal-nav {
    border: 0; background: transparent; width: 28px; height: 28px; border-radius: var(--ds-radius-1, 6px);
    color: var(--ds-ink-muted, #6c757d); display: inline-flex; align-items: center; justify-content: center;
}
.idcp-cal-nav:hover { background: var(--ds-surface-2, #f1f3f5); color: var(--ds-ink, #1f2937); }
.idcp-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.idcp-cal-dow { text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--ds-ink-muted, #6c757d); padding: 4px 0; }
.idcp-cal-day {
    aspect-ratio: 1 / 1; border: 0; background: transparent; border-radius: var(--ds-radius-1, 6px);
    font-size: 0.8125rem; color: var(--ds-ink, #1f2937); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
}
.idcp-cal-day:hover { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.1); }
.idcp-cal-day.in-range { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12); border-radius: 0; }
.idcp-cal-day.is-start, .idcp-cal-day.is-end { background: var(--bs-primary, #004a93); color: #fff; }
.idcp-cal-day.is-start { border-radius: var(--ds-radius-1, 6px) 0 0 var(--ds-radius-1, 6px); }
.idcp-cal-day.is-end { border-radius: 0 var(--ds-radius-1, 6px) var(--ds-radius-1, 6px) 0; }
.idcp-cal-day.is-start.is-end { border-radius: var(--ds-radius-1, 6px); }
.idcp-cal-footer {
    display: flex; align-items: center; justify-content: space-between; gap: var(--ds-space-2, 0.5rem);
    margin-top: var(--ds-space-3, 1rem); padding-top: var(--ds-space-3, 1rem); border-top: 1px solid var(--ds-line, #dee2e6);
}
.idcp-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted, #6c757d); }

/* Photo / document "Download" links inside the grid */
.dupidc-download-link { font-weight: 600; font-size: 0.8125rem; text-decoration: none; }
.dupidc-download-link:hover { text-decoration: underline; }

/* --- Scrollable grid ---------------------------------------------------
   This table has 17 columns. Rather than the DataTables Responsive
   extension hiding columns behind a per-row expand arrow, keep every
   column visible and let the panel scroll both ways. The header stays
   pinned while scrolling vertically. */
.duplicate-idcard-index-page .programme-dt-panel .table-responsive {
    max-height: 65vh;
    overflow: auto;
}
.duplicate-idcard-index-page .programme-dt-table thead th {
    position: sticky;
    top: 0;
    z-index: 3;
}
/* Hide the Responsive extension's expand control if it ever renders. */
.duplicate-idcard-index-page td.dtr-control,
.duplicate-idcard-index-page th.dtr-control { display: none !important; }
</style>
@endpush

@section('content')
<div class="container-fluid duplicate-idcard-index-page py-3">
    <x-breadcrum title="Request For Duplicate ID Card">
        <a href="{{ route('admin.duplicate_idcard.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add New Duplicate ID Card</span>
        </a>
    </x-breadcrum>
    <x-session_message />

    {{-- Print — above the card --}}
    <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 mb-3">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="dupIdcPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i> <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            {{-- Filter toolbar (programme-dt design system) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    {{-- Time Period (dual-month range calendar, filters Request Date) --}}
                    <div class="dropdown">
                        <button type="button" class="idcp-toggle dropdown-toggle"
                                id="dupIdcTimePeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                            <span id="dupIdcTimePeriodLabel">Time Period</span>
                        </button>
                        <div class="dropdown-menu p-0 idcp-menu">
                            <div class="idcp-cal" id="dupIdcCalendar">
                                <div class="idcp-cal-months">
                                    <div class="idcp-cal-month" data-month="0"></div>
                                    <div class="idcp-cal-month" data-month="1"></div>
                                </div>
                                <div class="idcp-cal-footer">
                                    <span class="idcp-cal-range" id="dupIdcCalRange">Select a date range</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="dupIdcClearPeriod">Clear</button>
                                        <button type="button" class="btn btn-sm btn-primary" id="dupIdcApplyPeriod">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="dupIdcResetFilters">Reset Filters</button>

                    {{-- Hidden inputs drive the client-side date filter. --}}
                    <input type="hidden" id="dupIdcDateFrom" value="{{ $dateFrom ?? request('date_from', '') }}">
                    <input type="hidden" id="dupIdcDateTo" value="{{ $dateTo ?? request('date_to', '') }}">
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="dupIdcBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#dupIdcColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="dupIdcDtSearch" class="programme-dt-search" data-dt-search-for="duplicateIdcardTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap align-middle programme-dt-table" id="duplicateIdcardTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>ID Card No</th>
                                <th>Date Of Birth</th>
                                <th>Blood Group</th>
                                <th>Contact No.</th>
                                <th>Reason</th>
                                <th>Employee Type</th>
                                <th>Employee Photo</th>
                                <th>Document (If Any)</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $r)
                                @php
                                    $reqTs = $r->request_date ? \Carbon\Carbon::parse($r->request_date)->timestamp : 0;
                                    $dobTs = $r->employee_dob ? \Carbon\Carbon::parse($r->employee_dob)->timestamp : 0;
                                    $vfTs = $r->valid_from ? \Carbon\Carbon::parse($r->valid_from)->timestamp : 0;
                                    $vtTs = $r->valid_to ? \Carbon\Carbon::parse($r->valid_to)->timestamp : 0;
                                    $statusLabel = (string) ($r->status_label ?? '');
                                    $statusClass = match (strtolower($statusLabel)) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'issued' => 'primary',
                                        'pending' => 'warning',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr data-ts="{{ $reqTs }}">
                                    <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                    <td>{{ $r->employee_name ?? '--' }}</td>
                                    <td>{{ $r->designation ?? '--' }}</td>
                                    <td>{{ $r->department ?? '--' }}</td>
                                    <td>{{ $r->id_card_no ?? '--' }}</td>
                                    <td data-order="{{ $dobTs }}">{{ $r->employee_dob ? \Carbon\Carbon::parse($r->employee_dob)->format('d-m-Y') : '--' }}</td>
                                    <td>{{ $r->blood_group ?? '--' }}</td>
                                    <td>{{ $r->mobile_no ?? '--' }}</td>
                                    <td>{{ $r->card_reason ?? '--' }}</td>
                                    <td>{{ $r->employee_type ?? '--' }}</td>
                                    <td>
                                        @php
                                            $p = $r->photo_path;
                                            if ($p && strpos($p, '/') === false) { $p = 'idcard/photos/' . $p; }
                                            $photoExists = $p && \Storage::disk('public')->exists($p);
                                        @endphp
                                        @if($photoExists)
                                            <a href="{{ asset('storage/' . $p) }}" target="_blank" class="dupidc-download-link text-primary">Download</a>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $d = $r->doc_path;
                                            if ($d && strpos($d, '/') === false) { $d = 'idcard/dup_docs/' . $d; }
                                            $docExists = $d && \Storage::disk('public')->exists($d);
                                        @endphp
                                        @if($docExists)
                                            <a href="{{ asset('storage/' . $d) }}" target="_blank" class="dupidc-download-link text-primary">Download</a>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td data-order="{{ $vfTs }}">{{ $r->valid_from ? \Carbon\Carbon::parse($r->valid_from)->format('d-m-Y') : '--' }}</td>
                                    <td data-order="{{ $vtTs }}">{{ $r->valid_to ? \Carbon\Carbon::parse($r->valid_to)->format('d-m-Y') : '--' }}</td>
                                    <td>
                                        @if($statusLabel !== '')
                                            <span class="badge rounded-1 bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td data-order="{{ $reqTs }}">{{ $r->request_date ? \Carbon\Carbon::parse($r->request_date)->format('d-m-Y') : '--' }}</td>
                                    <td class="text-center">
                                        @if(!empty($r->user_may_edit))
                                            <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                                                <a href="{{ route('admin.duplicate_idcard.edit', $r->id) }}" class="programme-action-btn" title="Edit">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </a>
                                                <form action="{{ route('admin.duplicate_idcard.destroy', $r->id) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Delete this duplicate ID card request? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete">
                                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="17" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">inbox</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No requests found.</p>
                                            <a href="{{ route('admin.duplicate_idcard.create') }}" class="btn btn-primary rounded-1 px-4 py-2 mt-2">Add Request</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="duplicateIdcardTable"></div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="dupIdcColumnVisibilityModal" tabindex="-1" aria-labelledby="dupIdcColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="dupIdcColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="dupIdcColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var TABLE_ID = '#duplicateIdcardTable';
    var $table = $(TABLE_ID);

    // No real data rows (only the empty-state CTA) -> skip DataTables so the CTA shows.
    if (!$table.length || $table.find('tbody tr[data-ts]').length === 0) { return; }

    /* ---- Client-side date-range filter (Request Date) ---- */
    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
        if (settings.nTable.id !== 'duplicateIdcardTable') { return true; }
        var row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!row) { return true; }
        var from = $('#dupIdcDateFrom').val();
        var to = $('#dupIdcDateTo').val();
        if (from || to) {
            var ts = parseInt(row.getAttribute('data-ts') || '0', 10);
            if (!ts) { return false; }
            if (from && ts < Math.floor(new Date(from + 'T00:00:00').getTime() / 1000)) { return false; }
            if (to && ts > Math.floor(new Date(to + 'T23:59:59').getTime() / 1000)) { return false; }
        }
        return true;
    });

    var table = $table.DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        // Show every column and let the panel scroll, instead of the Responsive
        // extension hiding columns behind a per-row expand arrow.
        responsive: false,
        order: [[15, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        columnDefs: [
            { targets: [0, 10, 11, 16], orderable: false, searchable: false }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search',
            paginate: { previous: '‹', next: '›' },
            lengthMenu: 'Showing _MENU_',
            info: 'of _TOTAL_ items',
            infoEmpty: 'of 0 items',
            infoFiltered: 'of _MAX_ items'
        },
        drawCallback: function () {
            var info = this.api().page.info();
            this.api().column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = info.start + i + 1;
            });
        }
    });

    /* ---- Branded print (LBSNAA header) ----
       Prints every row matching the current search + Time Period filter (not
       just the visible page), honours hidden columns, and drops the
       photo / document / actions columns which don't print meaningfully. ---- */
    var PRINT_SKIP_COLS = [10, 11, 16]; // Employee Photo, Document, Actions

    function cellText(html) {
        if (html === null || html === undefined) { return ''; }
        // Orthogonal cell data can arrive as an object — prefer its display value.
        if (typeof html === 'object') { html = html.display !== undefined ? html.display : ''; }
        var d = document.createElement('div');
        d.innerHTML = String(html);
        return (d.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function dupIdcPrintTable() {
        var printWindow = window.open('', '_blank');
        if (!printWindow) { alert('Please allow pop-ups for this site to print the report.'); return; }

        // Visible, printable columns in display order.
        var cols = [];
        table.columns().every(function () {
            var idx = this.index();
            if (this.visible() && PRINT_SKIP_COLS.indexOf(idx) === -1) { cols.push(idx); }
        });

        var headHtml = '<tr>' + cols.map(function (i) {
            return '<th>' + cellText($(table.column(i).header()).html()) + '</th>';
        }).join('') + '</tr>';

        // Read each cell's *display* value via the API. Cells carrying a
        // data-order attribute (the date columns) are stored by DataTables as
        // orthogonal objects, so rows().data() would yield an object rather
        // than the formatted date — render('display') resolves it correctly.
        var rowIdxs = table.rows({ search: 'applied', order: 'applied' }).indexes().toArray();
        var bodyHtml = rowIdxs.map(function (rowIdx, r) {
            return '<tr>' + cols.map(function (i) {
                // Column 0 is the running S.No — renumber for the printed sequence.
                if (i === 0) { return '<td>' + (r + 1) + '</td>'; }
                return '<td>' + cellText(table.cell(rowIdx, i).render('display')) + '</td>';
            }).join('') + '</tr>';
        }).join('');

        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
        var logoLeft  = @json(asset('admin_assets/images/logos/logo_new.png'));
        var logoRight = @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
            ? asset('admin_assets/images/logos/constitution-75.png')
            : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'));
        var titleHindi = @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'));

        // Show the applied Time Period range (if any) under the title.
        var from = $('#dupIdcDateFrom').val();
        var to = $('#dupIdcDateTo').val();
        var periodLine = (from || to) ? ('Time Period: ' + (from || '…') + ' to ' + (to || '…')) : '';

        var printContent =
            '<!DOCTYPE html><html><head><title>Duplicate ID Card Requests - Print</title><style>' +
            'body{font-family:Arial,sans-serif;margin:16px;color:#1f2937;}' +
            '.pdf-hdr{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
            '.pdf-hdr td{vertical-align:middle;} .pdf-hdr .logo{width:90px;text-align:center;}' +
            '.pdf-hdr .logo img{max-height:64px;max-width:84px;} .pdf-hdr .center{text-align:center;padding:0 8px;}' +
            '.pdf-hdr .inst-hi-img{height:18px;width:auto;margin-bottom:2px;}' +
            '.pdf-hdr .inst-en{font-size:16px;font-weight:bold;color:#102a43;line-height:1.25;}' +
            '.report-title{text-align:center;font-size:20px;font-weight:bold;color:#004a93;margin:8px 0 6px;padding-bottom:8px;border-bottom:2px solid #004a93;}' +
            '.print-info{margin-bottom:12px;font-size:11px;color:#666;text-align:center;}' +
            'table{width:100%;border-collapse:collapse;margin-top:10px;}' +
            'table th,table td{border:1px solid #8fa3bd;padding:6px 8px;text-align:left;font-size:11px;}' +
            'table thead th{font-weight:bold;background-color:#004a93 !important;color:#fff !important;text-align:center;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'table tbody tr:nth-child(even){background-color:#eef2f8;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.print-footer{margin-top:18px;text-align:center;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:10px;}' +
            '@media print{@page{size:A4 landscape;margin:10mm;} body{margin:0;}}' +
            '</style></head><body onload="window.focus();window.print();">' +
            '<table class="pdf-hdr"><tr>' +
                '<td class="logo"><img src="' + logoLeft + '" alt=""></td>' +
                '<td class="center"><img class="inst-hi-img" src="' + titleHindi + '" alt="">' +
                    '<div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>' +
                '</td>' +
                '<td class="logo"><img src="' + logoRight + '" alt=""></td>' +
            '</tr></table>' +
            '<div class="report-title">Duplicate ID Card Requests</div>' +
            '<div class="print-info"><div>Print Date: ' + dateStr + '</div>' +
                (periodLine ? '<div>' + periodLine + '</div>' : '') +
                '<div>Total Records: ' + rowIdxs.length + '</div></div>' +
            '<table><thead>' + headHtml + '</thead><tbody>' + bodyHtml + '</tbody></table>' +
            '<div class="print-footer"><p>Generated on ' + new Date().toLocaleString() + '</p></div>' +
            '</body></html>';

        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
    }

    $('#dupIdcPrintBtn').on('click', dupIdcPrintTable);

    /* ---- "Time Period" dual-month range calendar ---- */
    function updateTimePeriodLabel() {
        var from = $('#dupIdcDateFrom').val();
        var to = $('#dupIdcDateTo').val();
        $('#dupIdcTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
    }

    var dupIdcCal = (function initRangeCalendar() {
        var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var DOW = ['Mo','Tu','We','Th','Fr','Sa','Su'];
        var view = new Date(); view.setDate(1);
        var startD = null, endD = null;

        function pad(n){ return (n < 10 ? '0' : '') + n; }
        function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function parseYmd(s){ if (!s) { return null; } var p = String(s).split('-'); if (p.length < 3) { return null; } return new Date(+p[0], +p[1] - 1, +p[2]); }
        function sameDay(a, b){ return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }

        function buildMonth(base){
            var year = base.getFullYear(), month = base.getMonth();
            var startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var html = '<div class="idcp-cal-head">' +
                '<button type="button" class="idcp-cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>' +
                '<span class="idcp-cal-title">' + MONTHS[month] + ' ' + year + '</span>' +
                '<button type="button" class="idcp-cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>' +
                '</div><div class="idcp-cal-grid">';
            DOW.forEach(function(d){ html += '<span class="idcp-cal-dow">' + d + '</span>'; });
            for (var i = 0; i < startWeekday; i++) html += '<span></span>';
            for (var day = 1; day <= daysInMonth; day++){
                var d = new Date(year, month, day);
                var cls = 'idcp-cal-day';
                if (startD && endD && d > startD && d < endD) cls += ' in-range';
                if (sameDay(d, startD)) cls += ' is-start';
                if (sameDay(d, endD)) cls += ' is-end';
                html += '<button type="button" class="' + cls + '" data-date="' + ymd(d) + '">' + day + '</button>';
            }
            return html + '</div>';
        }

        function render(){
            var left = new Date(view.getFullYear(), view.getMonth(), 1);
            var right = new Date(view.getFullYear(), view.getMonth() + 1, 1);
            $('#dupIdcCalendar .idcp-cal-month[data-month="0"]').html(buildMonth(left));
            $('#dupIdcCalendar .idcp-cal-month[data-month="1"]').html(buildMonth(right));
            $('#dupIdcCalendar .idcp-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#dupIdcCalendar .idcp-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  →  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  → …';
            $('#dupIdcCalRange').text(label);
        }

        $('#dupIdcCalendar').on('click', '.idcp-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
        });
        $('#dupIdcCalendar').on('click', '.idcp-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#dupIdcApplyPeriod').on('click', function(){
            $('#dupIdcDateFrom').val(startD ? ymd(startD) : '');
            $('#dupIdcDateTo').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            updateTimePeriodLabel();
            table.draw();
            if (window.bootstrap) { bootstrap.Dropdown.getOrCreateInstance(document.getElementById('dupIdcTimePeriodToggle')).hide(); }
        });
        $('#dupIdcClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#dupIdcDateFrom').val(''); $('#dupIdcDateTo').val('');
            updateTimePeriodLabel(); table.draw();
        });

        // Seed from any request date range so the picker shows selected on load.
        startD = parseYmd($('#dupIdcDateFrom').val());
        endD = parseYmd($('#dupIdcDateTo').val());
        if (startD) { view = new Date(startD.getFullYear(), startD.getMonth(), 1); }
        render();
        updateTimePeriodLabel();

        return { reset: function(){ startD = null; endD = null; view = new Date(); view.setDate(1); render(); } };
    })();

    $('#dupIdcResetFilters').on('click', function () {
        $('#dupIdcDateFrom').val('');
        $('#dupIdcDateTo').val('');
        if (dupIdcCal) { dupIdcCal.reset(); }
        updateTimePeriodLabel();
        table.search('').draw();
    });

    /* ---- Column show / hide ---- */
    var colKey = 'duplicateIdcardGrid:hiddenColumns:v1';
    function getHidden() { try { var a = JSON.parse(localStorage.getItem(colKey) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function setHidden(a) { try { localStorage.setItem(colKey, JSON.stringify(a)); } catch (e) {} }

    function setupColumns(dt) {
        var hidden = getHidden();
        dt.columns().every(function () { var idx = this.index(); this.visible(hidden.indexOf(idx) === -1, false); });
        dt.columns.adjust();

        var $grid = $('#dupIdcColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }
            var inputId = 'dupidccolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
            $cb.on('change', function () {
                var h = getHidden(); var pos = h.indexOf(idx);
                if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                setHidden(h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
            });
            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label); $grid.append($cell);
        });
    }

    setupColumns(table);
});
</script>
@endpush
