@extends('admin.layouts.master')
@section('title', 'My Reported Issues')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/issue-reports-admin.css') }}?v={{ @filemtime(public_path('css/issue-reports-admin.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid ir-page">
    <x-breadcrum title="My Reported Issues" />

    {{-- Status pills + exports — above the card (new-design §1) --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter issues by status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        data-filter="all" aria-pressed="true" aria-current="true">All Issues</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        data-filter="active" aria-pressed="false">Active Issues</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        data-filter="fixed" aria-pressed="false">Fixed Issues</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap gap-2 no-print">
            <div class="dropdown">
                <button type="button" class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a id="myDownloadCsvBtn" class="dropdown-item" href="{{ route('my.issue-reports.export') }}">
                            <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV
                        </a>
                    </li>
                    <li>
                        <a id="myDownloadExcelBtn" class="dropdown-item" href="{{ route('my.issue-reports.export-excel') }}">
                            <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel
                        </a>
                    </li>
                </ul>
            </div>

            <button type="button" id="myPrintBtn" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            {{-- Toolbar: filters left · columns + search right (new-design §2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4
                        programme-dt-toolbar no-print">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select id="myDeptFilter" class="form-select" aria-label="Filter by department">
                            <option value="">Department</option>
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="mySubmoduleFilter" class="form-select" aria-label="Filter by submodule">
                            <option value="">Submodule</option>
                        </select>
                    </div>

                    <div class="ir-date-range">
                        <input type="date" id="myDateFrom" class="form-control ir-date-input" aria-label="From date">
                        <span class="ir-date-sep" aria-hidden="true">—</span>
                        <input type="date" id="myDateTo" class="form-control ir-date-input" aria-label="To date">
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="myRemoveFilterBtn">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#myIssueReportColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="myIssueDtSearch" class="programme-dt-search" data-dt-search-for="my-issue-reports-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
            </div>

            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 no-print"
                 data-dt-footer-for="my-issue-reports-table"></div>

        </div>
    </div>
</div>

{{-- ── Column visibility ── --}}
<div class="modal fade ir-modal" id="myIssueReportColumnVisibilityModal" tabindex="-1"
     aria-labelledby="myIssueReportColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="ir-modal-header">
                <h5 class="ir-modal-title" id="myIssueReportColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="ir-modal-body">
                <div class="row g-3" id="myIssueReportColumnToggleGrid"></div>
            </div>
            <div class="ir-modal-footer">
                <button type="button" class="btn ir-btn-submit" data-bs-dismiss="modal">Close</button>
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

    /* ── Collect active filter state ── */
    function filterParams() {
        return {
            status_filter:    currentFilter,
            dept_filter:      $('#myDeptFilter').val()      || '',
            submodule_filter: $('#mySubmoduleFilter').val() || '',
            date_from:        $('#myDateFrom').val()        || '',
            date_to:          $('#myDateTo').val()          || '',
        };
    }

    /* ── Build a <thead>/<tbody> HTML string from the currently visible columns/rows ── */
    function buildPrintableTableHtml() {
        if (!table) return '';
        var vis = [];
        table.columns().every(function (i) {
            var title = $(this.header()).text().trim();
            if (!title || title === 'Action') return;
            if (this.visible()) vis.push(i);
        });

        var html = '<thead><tr>';
        vis.forEach(function (ci) {
            html += '<th>' + ($(table.column(ci).header()).text() || '').trim() + '</th>';
        });
        html += '</tr></thead><tbody>';

        table.rows({ search: 'applied' }).nodes().each(function (rowNode) {
            var $row = $(rowNode);
            if ($row.hasClass('child')) return;
            html += '<tr>';
            vis.forEach(function (ci) {
                var cellNode = table.cell(rowNode, ci).node();
                var cellHtml = '';
                if (cellNode) {
                    var $cell = $(cellNode).clone();
                    $cell.find('input,button,select,textarea,a').each(function () {
                        var $el = $(this);
                        if ($el.is('a')) { $el.replaceWith($el.text()); }
                        else { $el.remove(); }
                    });
                    cellHtml = ($cell.html() || '').trim();
                }
                html += '<td>' + cellHtml + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody>';
        return html;
    }

    /* ── Open a formatted print window (Govt. of India letterhead + report table) ── */
    function openPrintWindow(tableHtml, reportTitle) {
        var emblemUrl = '{{ asset("images/ashoka.png") }}';
        var logoUrl   = '{{ asset("admin_assets/images/logos/logo.png") }}';

        var filterParts = [];
        var tabLabel = $('.programme-status-pill.active').text().trim();
        if (tabLabel && tabLabel !== 'All Issues') filterParts.push(tabLabel);
        var deptText = $('#myDeptFilter option:selected').text().trim();
        if ($('#myDeptFilter').val()) filterParts.push('Department: ' + deptText);
        var subText = $('#mySubmoduleFilter option:selected').text().trim();
        if ($('#mySubmoduleFilter').val()) filterParts.push('Submodule: ' + subText);
        if ($('#myDateFrom').val()) filterParts.push('From: ' + $('#myDateFrom').val());
        if ($('#myDateTo').val())   filterParts.push('To: ' + $('#myDateTo').val());
        var filterLine = filterParts.length ? filterParts.join(' | ') : 'No filters applied';

        var printWindow = window.open('', '_blank');
        if (!printWindow) { window.print(); return; }

        printWindow.document.open();
        printWindow.document.write('<!doctype html>\n' +
'<html lang="en">\n' +
'<head>\n' +
'    <meta charset="utf-8">\n' +
'    <title>' + reportTitle + ' - LBSNAA MUSSOORIE</title>\n' +
'    <style>\n' +
'        *, *::before, *::after { box-sizing: border-box; }\n' +
'        body {\n' +
'            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;\n' +
'            font-size: 11px;\n' +
'            color: #212529;\n' +
'            -webkit-print-color-adjust: exact;\n' +
'            print-color-adjust: exact;\n' +
'            margin: 0;\n' +
'            padding: 12mm 10mm;\n' +
'        }\n' +
'        .print-header { display: flex; align-items: center; gap: 12px; border-bottom: 3px solid #004a93; padding-bottom: 10px; margin-bottom: 12px; }\n' +
'        .print-header img { height: 48px; width: auto; object-fit: contain; }\n' +
'        .header-text { flex: 1; }\n' +
'        .header-text .line1 { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #004a93; font-weight: 600; margin: 0; }\n' +
'        .header-text .line2 { font-size: 14px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 2px 0 0; }\n' +
'        .header-text .line3 { font-size: 9px; color: #555; margin: 1px 0 0; }\n' +
'        .report-title-block { text-align: center; margin-bottom: 10px; }\n' +
'        .report-title-block h2 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 4px; color: #1a1a1a; }\n' +
'        .date-pill { display: inline-block; background: #004a93; color: #fff; padding: 3px 14px; border-radius: 10px; font-size: 10px; font-weight: 500; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #004a93; }\n' +
'        .report-meta { font-size: 10px; line-height: 1.7; margin: 8px 0 10px; color: #333; }\n' +
'        .report-meta strong { color: #1a1a1a; }\n' +
'        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }\n' +
'        .data-table th, .data-table td { padding: 4px 6px; border: 1px solid #bbb; vertical-align: middle; word-break: break-word; white-space: normal; }\n' +
'        .data-table thead th { background: #004a93; color: #fff; font-weight: 600; font-size: 10px; text-align: left; }\n' +
'        .data-table tbody tr:nth-child(even) td { background: #f9fafb; }\n' +
'        .footer { border-top: 1px solid #dee2e6; font-size: 8px; color: #666; text-align: center; padding-top: 4px; margin-top: 8px; }\n' +
'        @page { size: A4 landscape; margin: 8mm; }\n' +
'        @media print { body { padding: 0; } thead { display: table-header-group; } tr { page-break-inside: avoid; } }\n' +
'    </style>\n' +
'</head>\n' +
'<body>\n' +
'<div class="print-header">\n' +
'    <img src="' + emblemUrl + '" alt="Emblem">\n' +
'    <div class="header-text">\n' +
'        <p class="line1">Government of India</p>\n' +
'        <p class="line2">LBSNAA MUSSOORIE</p>\n' +
'        <p class="line3">Lal Bahadur Shastri National Academy of Administration</p>\n' +
'    </div>\n' +
'    <img src="' + logoUrl + '" alt="LBSNAA Logo" onerror="this.style.display=\'none\'">\n' +
'</div>\n' +
'<div class="report-title-block">\n' +
'    <h2>' + reportTitle + '</h2>\n' +
'    <span class="date-pill">' + filterLine + '</span>\n' +
'</div>\n' +
'<div class="report-meta">\n' +
'    <strong>Printed:</strong> ' + new Date().toLocaleDateString('en-IN') + ' ' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) + '\n' +
'</div>\n' +
'<table class="data-table">\n' + tableHtml + '\n</table>\n' +
'<div class="footer"><small>LBSNAA Mussoorie &mdash; ' + reportTitle + '</small></div>\n' +
'<script>\n' +
'    window.addEventListener("load", function() {\n' +
'        setTimeout(function() { window.print(); }, 300);\n' +
'    });\n' +
'<\/script>\n' +
'</body>\n' +
'</html>');
        printWindow.document.close();
    }

    /* ── Column title -> export field key, used to restrict Download to checked columns ── */
    var COLUMN_KEY_MAP = {
        'S. No.':            'sno',
        'Date':               'date',
        'Department Name':    'dept_name',
        'Sub-Module Name':    'sub_module_name',
        'Issue Description':  'description',
        'Attachment':         'attachment',
        'Status':             'status'
    };

    /* ── Keep Export links (CSV + Excel) in sync with active filters + visible columns ── */
    function updateDownloadLink() {
        var p  = filterParams();
        var qs = Object.entries(p)
            .filter(function (e) { return e[1] !== ''; })
            .map(function (e) { return encodeURIComponent(e[0]) + '=' + encodeURIComponent(e[1]); });

        var visibleKeys = [];
        $('#myIssueReportColumnToggleGrid input[type=checkbox]:checked').each(function () {
            var key = COLUMN_KEY_MAP[$(this).data('label')];
            if (key) visibleKeys.push(key);
        });
        if (visibleKeys.length) {
            qs.push('columns=' + encodeURIComponent(visibleKeys.join(',')));
        }

        var suffix = qs.length ? '?' + qs.join('&') : '';
        $('#myDownloadCsvBtn').attr('href', '{{ route('my.issue-reports.export') }}' + suffix);
        $('#myDownloadExcelBtn').attr('href', '{{ route('my.issue-reports.export-excel') }}' + suffix);
    }

    /* ── Column visibility ─────────────────────────────────────────────────
       Hidden columns are stored as LABELS, not indices: a column added to the
       table later would shift every index and silently hide the wrong one
       (docs/column-visibility.md §3). */
    var COLVIS_KEY = 'sargam.myIssueReports.hiddenCols.{{ auth()->id() ?? 'guest' }}';

    function readHidden() {
        try {
            var raw = window.localStorage.getItem(COLVIS_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }

    function persistHidden(labels) {
        try { window.localStorage.setItem(COLVIS_KEY, JSON.stringify(labels)); } catch (e) {}
    }

    function buildColumnsModal(dt) {
        var $grid = $('#myIssueReportColumnToggleGrid');
        if (!$grid.length) return;

        var hidden = readHidden();
        $grid.empty();

        dt.columns().every(function () {
            var idx   = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) return;

            var shown   = hidden.indexOf(title) === -1;
            var inputId = 'myIrColvis_' + idx;

            this.visible(shown, false);

            var $cell  = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .data('label', title)
                .prop('checked', shown);

            $cb.on('change', function () {
                var h = readHidden();
                var pos = h.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else if (pos === -1) {
                    h.push(title);
                }
                persistHidden(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
                updateDownloadLink();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });

        dt.columns.adjust();
        updateDownloadLink();
    }

    /* ── DataTable hookup ──────────────────────────────────────────────────
       Search box and footer are relocated by datatable-global-ui.js into the
       .programme-dt-search / .programme-dt-footer slots above — nothing to do
       here beyond the filters. */
    $(document).on('init.dt', function (e, settings) {
        if (!settings.nTable || settings.nTable.id !== 'my-issue-reports-table') return;

        table = new $.fn.dataTable.Api(settings);

        /* Inject filter params into every AJAX request */
        $('#my-issue-reports-table').on('preXhr.dt', function (ev, s, data) {
            $.extend(data, filterParams());
        });

        buildColumnsModal(table);
    });

    /* ── Status pills ── */
    $(document).on('click', '.programme-status-pill', function () {
        $('.programme-status-pill').removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $(this).addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });
        currentFilter = String($(this).data('filter'));
        updateDownloadLink();
        if (table) table.ajax.reload();
    });

    /* ── Filter controls ── */
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

    /* ── Print ── */
    $('#myPrintBtn').on('click', function () {
        if (!table) { window.print(); return; }
        var originalLen  = table.page.len();
        var originalPage = table.page();
        var restored     = false;

        var restore = function () {
            if (restored) return;
            restored = true;
            table.page.len(originalLen);
            table.page(originalPage);
            table.draw(false);
        };

        table.one('draw', function () {
            setTimeout(function () {
                openPrintWindow(buildPrintableTableHtml(), 'My Reported Issues Report');
                setTimeout(restore, 800);
            }, 250);
        });

        table.page.len(-1).draw();
    });

    /* ── Populate filter dropdowns ── */
    $.get('{{ route('my.issue-reports.filter-options') }}', function (data) {
        var $dept = $('#myDeptFilter');
        var $sub  = $('#mySubmoduleFilter');
        (data.departments || []).forEach(function (d) {
            $dept.append($('<option>').val(d).text(d));
        });
        (data.submodules || []).forEach(function (s) {
            $sub.append($('<option>').val(s).text(s));
        });
    });

    updateDownloadLink();
});
</script>
@endpush
