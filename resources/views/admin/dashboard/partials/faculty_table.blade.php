{{--
    Shared Guest / In-House faculty listing table.

    Required data:
      $faculties    - collection of faculty rows
      $tableId      - unique DOM id for the table (e.g. "guess_faculty")
      $cssFile      - page-specific stylesheet path (asset())
      $cardClass    - card wrapper modifier class
      $pageTitle    - breadcrumb / heading title
      $exportTitle  - title used for export + print output
      $badgeClass   - faculty-type badge modifier class
      $badgeLabel   - faculty-type badge text
      $emptyMessage - message shown when there are no rows
--}}
<link rel="stylesheet" href="{{ asset($cssFile) }}">

@push('styles')
<style>
    /* ---- Faculty table toolbar (shared Guest / In-House) ---- */
    .dt-external-toolbar {
        background: #fff;
        border: 1px solid #eceef1;
        border-radius: .75rem;
        padding: .625rem .75rem;
        row-gap: .5rem;
    }

    /* Native length + search, relocated into the toolbar */
    .dt-external-toolbar .dataTables_length,
    .dt-external-toolbar .dataTables_filter {
        margin: 0;
    }
    .dt-external-toolbar .dataTables_length label,
    .dt-external-toolbar .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        margin: 0;
        font-size: .8125rem;
        font-weight: 500;
        color: #6c757d;
    }
    .dt-external-toolbar .dataTables_length select.form-select {
        width: auto;
        min-width: 4.75rem;
        border-radius: .5rem;
    }
    .dt-external-toolbar .dataTables_filter input.form-control {
        min-width: 230px;
        border-radius: .5rem;
        padding-left: 2rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23adb5bd'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: .6rem center;
        background-size: .85rem;
    }
    .dt-external-toolbar .dataTables_filter input.form-control:focus,
    .dt-external-toolbar .dataTables_length select.form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15);
    }

    /* Unified toolbar buttons with a Bootstrap 5.3 colour hierarchy:
       Columns = secondary, Export = success, Print = primary. */
    .dt-external-toolbar .dt-buttons { margin: 0; }
    .dt-external-toolbar .dtb-btn,
    .dt-external-toolbar .dt-buttons .dt-button.dtb-btn {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        margin: 0;
        padding: .375rem .75rem;
        border: 1px solid transparent;
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 600;
        line-height: 1.2;
        background-color: #fff;
        box-shadow: none;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
    }
    .dt-external-toolbar .dtb-btn i { line-height: 1; }

    /* Columns — secondary */
    .dt-external-toolbar .dtb-columns { border-color: #ced4da !important; color: #495057 !important; }
    .dt-external-toolbar .dtb-columns:hover,
    .dt-external-toolbar .dtb-columns.show { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }

    /* Export — success */
    .dt-external-toolbar .dtb-export { border-color: #198754 !important; color: #198754 !important; }
    .dt-external-toolbar .dtb-export:hover,
    .dt-external-toolbar .dtb-export.show { background-color: #198754 !important; border-color: #198754 !important; color: #fff !important; }

    /* Print — primary */
    .dt-external-toolbar .dtb-print { border-color: #0d6efd !important; color: #0d6efd !important; }
    .dt-external-toolbar .dtb-print:hover,
    .dt-external-toolbar .dtb-print:focus { background-color: #0d6efd !important; border-color: #0d6efd !important; color: #fff !important; }

    .dt-external-toolbar .dtb-btn:focus-visible { box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .25); }

    /* Columns dropdown menu + export collection menu */
    .dt-external-toolbar .dropdown-menu,
    .dt-button-collection.dropdown-menu {
        border: 1px solid #eceef1;
        border-radius: .625rem;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .1);
        padding: .375rem;
    }
    .dt-external-toolbar .dropdown-menu .dropdown-header {
        color: #adb5bd;
        letter-spacing: .04em;
        font-weight: 600;
    }
    .dt-external-toolbar .dropdown-menu .dropdown-item,
    .dt-button-collection .dt-button {
        border-radius: .375rem;
        padding: .4rem .6rem;
        font-size: .8125rem;
    }
    .dt-external-toolbar .dropdown-menu .dropdown-item:hover,
    .dt-button-collection .dt-button:hover {
        background-color: #f1f5f9;
        color: #0d6efd;
    }
    .dt-external-toolbar .dropdown-menu .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    @media (max-width: 575.98px) {
        .dt-external-toolbar .dataTables_filter input.form-control { min-width: 0; width: 100%; }
        .dt-external-toolbar .dataTables_filter { flex: 1 1 100%; }
    }
</style>
@endpush

<div class="container-fluid px-2 px-md-3">
    <x-breadcrum title="{{ $pageTitle }}"></x-breadcrum>
    <div class="card {{ $cardClass }} border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            <div class="datatables">
                {{-- External toolbar (kept OUTSIDE .table-responsive so dropdown menus are not clipped).
                     The native DataTables length + search controls are relocated into the left side
                     in initComplete so everything sits on a single line. --}}
                <div class="dt-external-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"
                     id="{{ $tableId }}_toolbar">
                    <div class="dt-toolbar-left d-flex flex-wrap align-items-center gap-2"></div>
                    <div class="dt-toolbar-right d-flex flex-wrap align-items-center gap-2"></div>
                </div>
                <div class="table-responsive">
                    {{-- Opt out of the global SargamDataTableUI auto-enhancer so it does not
                         fight this page's own toolbar layout (search/length relocation, etc.). --}}
                    <table class="table table-hover align-middle text-nowrap mb-0" id="{{ $tableId }}"
                        data-sargam-dt-ui="false">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Faculty Type</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Mobile Number</th>
                                <th scope="col">Current Sector</th>
                                <th scope="col">Session Count</th>
                                <th scope="col">Feedback Average</th>
                                @if(hasRole('Admin'))
                                <th scope="col" class="dt-no-export">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($faculties as $index => $faculty)
                            <tr>
                                <td class="text-body-secondary fw-medium">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge rounded-1 {{ $badgeClass }} bg-success-subtle text-success border border-success-subtle">{{ $badgeLabel }}</span>
                                </td>
                                <td>
                                    <span class="faculty-name">{{ $faculty->full_name }}</span>
                                </td>
                                <td>
                                    @if($faculty->email_id ?? null)
                                        <a href="mailto:{{ $faculty->email_id }}" class="email-link">
                                            <span class="material-symbols-rounded align-text-bottom me-1" style="font-size: 1rem;">mail</span>
                                            {{ $faculty->email_id }}
                                        </a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                                <td class="fw-medium">{{ $faculty->mobile_no ?? 'N/A' }}</td>
                                <td>
                                    @if($faculty->faculty_sector == 1)
                                        <span class="badge rounded-1 badge-sector-gov border border-primary-subtle">Government</span>
                                    @elseif($faculty->faculty_sector == 2)
                                        <span class="badge rounded-1 badge-sector-private border border-warning-subtle">Private</span>
                                    @else
                                        <span class="badge rounded-1 badge-sector-other border border-secondary-subtle">Other</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="session-count-badge d-inline-flex align-items-center gap-1">
                                        <span class="material-symbols-rounded align-text-bottom" style="font-size: 1rem;">event</span>
                                        {{ $faculty->session_count ?? 0 }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $avgContent = data_get($faculty, 'feedback_summary.avg_content', 0);
                                        $avgPresentation = data_get($faculty, 'feedback_summary.avg_presentation', 0);
                                        $totalFeedback = (int) data_get($faculty, 'feedback_summary.total_feedback', 0);
                                        $getScoreClass = function($score) {
                                            if ($score >= 80) return 'excellent';
                                            if ($score >= 60) return 'good';
                                            if ($score >= 40) return 'average';
                                            return 'poor';
                                        };
                                    @endphp
                                    @if($totalFeedback > 0)
                                        <div class="feedback-average">
                                            <div class="feedback-score">
                                                <span class="feedback-label">Content:</span>
                                                <span class="feedback-value {{ $getScoreClass($avgContent) }}">
                                                    {{ number_format($avgContent, 1) }}%
                                                </span>
                                            </div>
                                            <div class="feedback-score">
                                                <span class="feedback-label">Presentation:</span>
                                                <span class="feedback-value {{ $getScoreClass($avgPresentation) }}">
                                                    {{ number_format($avgPresentation, 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">No feedback yet</span>
                                    @endif
                                </td>
                                @if(hasRole('Admin'))
                                <td class="dt-no-export">
                                    <a href="{{ route('feedback.average', ['faculty_name' => $faculty->full_name]) }}"
                                       class="btn btn-view-feedback btn-sm">
                                        <span class="material-symbols-rounded">visibility</span>
                                        View Feedback
                                    </a>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="no-data text-center py-5 text-body-secondary fst-italic">
                                    <span class="material-symbols-rounded fs-1 d-block mb-2 opacity-50">person_off</span>
                                    {{ $emptyMessage }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    var tableId = '{{ $tableId }}';
    var exportTitle = @json($exportTitle);
    var $toolbar = $('#' + tableId + '_toolbar');

    // LBSNAA report branding (mirrors the Mess report theme)
    var brandEmblem = @json('https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png');
    var brandLogo   = @json(asset('admin_assets/images/logos/logo.png'));
    var brandLine1  = 'Government of India';
    var brandLine2  = 'Lal Bahadur Shastri National Academy of Administration';
    var brandLine3  = 'Mussoorie, Uttarakhand';

    var table = $('#' + tableId).DataTable({
        order: [[0, 'asc']], // Sort by S. No. by default
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        // Persist column visibility (and search/sort/page) across navigation & refresh.
        stateSave: true,
        stateDuration: 60 * 60 * 24 * 30, // 30 days (localStorage)
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: false,
        autoWidth: false,
        // The length (l) + search (f) live in a hidden top row and are relocated
        // into the external toolbar in initComplete so the whole control bar is
        // one line. info (i) + pagination (p) stay below the table.
        dom: '<"dt-top-row d-none"lf>rt<"row g-2 align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
        initComplete: function() {
            var api = this.api();
            var wrapper = $('#' + tableId + '_wrapper');

            var $length = wrapper.find('.dataTables_length');
            var $filter = wrapper.find('.dataTables_filter');

            $length.find('select').addClass('form-select form-select-sm');
            $filter.find('input').addClass('form-control').attr('placeholder', 'Search faculty...');
            $length.addClass('mb-0');
            $filter.addClass('mb-0');
            wrapper.find('.dataTables_info').addClass('small text-muted');
            wrapper.find('.dataTables_paginate').addClass('small');

            // Relocate native length + search into the single-line toolbar (left side).
            $toolbar.find('.dt-toolbar-left').append($length).append($filter);

            buildColumnToggle(api);
            buildExportPrint(api);
        }
    });

    // ---- Column Show / Hide (Bootstrap dropdown, DataTables core only) ----
    function buildColumnToggle(api) {
        var $dropdown = $(
            '<div class="dropdown">' +
                '<button class="btn btn-sm dtb-btn dtb-columns dropdown-toggle" ' +
                    'type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">' +
                    '<i class="bi bi-layout-three-columns"></i><span class="d-none d-sm-inline">Columns</span>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end p-2 shadow-sm" style="min-width: 220px;">' +
                    '<li class="dropdown-header px-2 pt-0 pb-1 text-uppercase small">Show / Hide</li>' +
                '</ul>' +
            '</div>'
        );
        var $menu = $dropdown.find('.dropdown-menu');

        api.columns().every(function(index) {
            var column = this;
            var title = $(column.header()).text().trim() || ('Column ' + (index + 1));
            var $item = $(
                '<li>' +
                    '<label class="dropdown-item d-flex align-items-center gap-2 mb-0">' +
                        '<input type="checkbox" class="form-check-input m-0"' + (column.visible() ? ' checked' : '') + '>' +
                        '<span></span>' +
                    '</label>' +
                '</li>'
            );
            $item.find('span').text(title);
            $item.find('input').on('change', function() {
                // DataTables persists this via stateSave automatically.
                column.visible($(this).is(':checked'));
            });
            $menu.append($item);
        });

        $toolbar.find('.dt-toolbar-right').append($dropdown);
    }

    // ---- Export (Excel/CSV/PDF) + Print — only if Buttons extension exists ----
    function buildExportPrint(api) {
        if (!($.fn.dataTable && $.fn.dataTable.Buttons)) {
            return; // Buttons not loaded — skip gracefully, table stays functional.
        }

        // Strip icon ligatures (mail / event / visibility), action buttons and
        // other UI chrome from a cell so only real data text is exported.
        function cleanCellText(innerHtml, node) {
            if (node && node.cloneNode) {
                var clone = node.cloneNode(true);
                clone.querySelectorAll(
                    '.material-symbols-rounded, .material-icons, i, button, a.btn, .btn, [aria-hidden="true"]'
                ).forEach(function (el) { el.parentNode && el.parentNode.removeChild(el); });
                return (clone.textContent || '').replace(/\s+/g, ' ').trim();
            }
            return String(innerHtml || '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
        }

        // Export only visible, non-action columns.
        function exportableColumns(idx) {
            return api.column(idx).visible() && !$(api.column(idx).header()).hasClass('dt-no-export');
        }

        // Shared options: column filtering + text cleaning for every format.
        // DataTables passes body as (data, row, column, node) and header as (data, column).
        var sharedExportOptions = {
            columns: exportableColumns,
            format: {
                body: function (data, row, column, node) {
                    return cleanCellText(data, node);
                },
                header: function (data) {
                    return cleanCellText(data, null);
                }
            }
        };

        // Mess-report print theme: LBSNAA branding header, blue (#004a93) accents,
        // grey table headers, zebra rows. Non-cropping A4 landscape.
        var PRINT_CSS =
            '@page { size: A4 landscape; margin: 12mm; }' +
            '* { box-sizing: border-box; }' +
            'body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11pt; margin: 0; padding: 0; ' +
                'color: #222; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
            '.lbsnaa-header-wrap { border-bottom: 2px solid #004a93; margin-bottom: 12px; padding: 2px 0 8px; }' +
            '.branding-table { width: 100%; border-collapse: collapse; margin: 0; }' +
            '.branding-table td { border: 0; padding: 0; vertical-align: middle; }' +
            '.branding-logo-left { width: 42px; }' +
            '.branding-text { text-align: left; padding: 0 10px 0 2px; line-height: 1.25; }' +
            '.branding-logo-right { width: 200px; text-align: right; }' +
            '.lbsnaa-brand-line-1 { font-size: 8pt; color: #004a93; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }' +
            '.lbsnaa-brand-line-2 { font-size: 13pt; color: #222; font-weight: 700; text-transform: uppercase; margin-top: 2px; }' +
            '.lbsnaa-brand-line-3 { font-size: 10pt; color: #555; margin-top: 2px; }' +
            '.header-img-left { width: 34px; height: 34px; }' +
            '.header-img-right { width: 165px; height: auto; }' +
            '.report-header-block { text-align: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #dee2e6; }' +
            '.report-title-center { font-size: 14pt; font-weight: 700; text-transform: uppercase; margin: 0 0 8px; color: #212529; }' +
            '.report-date-bar { background: #004a93; color: #fff; padding: 8px 12px; text-align: center; font-weight: 600; font-size: 10pt; display: inline-block; }' +
            '.report-meta-print { font-size: 9pt; margin: 10px 0 12px; line-height: 1.45; text-align: left; }' +
            '.report-meta-print .meta-line { margin-bottom: 4px; word-wrap: break-word; }' +
            'table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 10px; table-layout: auto; }' +
            'thead { display: table-header-group; }' +
            'th, td { padding: 5px 8px; border: 1px solid #dee2e6; vertical-align: top; ' +
                'white-space: normal; word-wrap: break-word; overflow-wrap: anywhere; }' +
            'thead th { background: #d3d6d9 !important; font-weight: 600; text-align: left; }' +
            'tbody tr:nth-child(even) td { background: #fafbfc !important; }' +
            'tr { page-break-inside: avoid; }' +
            '.report-footer { border-top: 1px solid #dee2e6; font-size: 8pt; color: #666; text-align: center; padding-top: 6px; margin-top: 8px; }';

        function escapeHtml(str) {
            return String(str).replace(/[&<>"]/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
            });
        }

        function customizePrint(win) {
            var doc = win.document;
            var recordCount = api.rows({ search: 'applied' }).count();
            var printedOn = new Date().toLocaleString();

            var style = doc.createElement('style');
            style.appendChild(doc.createTextNode(PRINT_CSS));
            doc.head.appendChild(style);

            var defaultTitle = doc.querySelector('h1');
            if (defaultTitle) { defaultTitle.parentNode.removeChild(defaultTitle); }

            doc.body.insertAdjacentHTML('afterbegin',
                '<div class="lbsnaa-header-wrap">' +
                    '<table class="branding-table"><tr>' +
                        '<td class="branding-logo-left"><img src="' + brandEmblem + '" alt="Emblem of India" class="header-img-left"></td>' +
                        '<td class="branding-text">' +
                            '<div class="lbsnaa-brand-line-1">' + escapeHtml(brandLine1) + '</div>' +
                            '<div class="lbsnaa-brand-line-2">' + escapeHtml(brandLine2) + '</div>' +
                            '<div class="lbsnaa-brand-line-3">' + escapeHtml(brandLine3) + '</div>' +
                        '</td>' +
                        '<td class="branding-logo-right"><img src="' + brandLogo + '" alt="LBSNAA Logo" class="header-img-right"></td>' +
                    '</tr></table>' +
                '</div>' +
                '<div class="report-header-block">' +
                    '<h1 class="report-title-center">' + escapeHtml(exportTitle) + ' Report</h1>' +
                    '<div class="report-date-bar">Total Records: ' + recordCount + '</div>' +
                '</div>' +
                '<div class="report-meta-print">' +
                    '<div class="meta-line"><strong>Printed on:</strong> ' + escapeHtml(printedOn) + '</div>' +
                '</div>'
            );
            doc.body.insertAdjacentHTML('beforeend',
                '<div class="report-footer"><small>' + escapeHtml(brandLine2) + ' &mdash; ' + escapeHtml(exportTitle) + ' Report</small></div>'
            );
        }

        // PDF (pdfmake) theme matched to the Mess report look: blue title, grey
        // header fill, zebra rows, page numbers. (pdfmake cannot embed the logo
        // images the way the server-side Mess PDFs do, so it uses a text header.)
        function customizePdf(doc) {
            try {
                doc.pageMargins = [22, 26, 22, 32];
                doc.defaultStyle.fontSize = 8;

                doc.styles = doc.styles || {};
                doc.styles.title = { fontSize: 14, bold: true, color: '#212529', alignment: 'center', margin: [0, 0, 0, 8] };
                doc.styles.tableHeader = { bold: true, fontSize: 8, color: '#212529', fillColor: '#d3d6d9' };

                // Branding lines above the title.
                doc.content.unshift(
                    { text: brandLine1, fontSize: 8, color: '#004a93', alignment: 'center', characterSpacing: 0.5 },
                    { text: brandLine2.toUpperCase(), fontSize: 12, bold: true, alignment: 'center', margin: [0, 2, 0, 2] },
                    { canvas: [{ type: 'line', x1: 0, y1: 0, x2: 751, y2: 0, lineWidth: 1.5, lineColor: '#004a93' }], margin: [0, 0, 0, 8] }
                );

                var tableNode = doc.content.find(function (c) { return c && c.table; });
                if (tableNode) {
                    tableNode.layout = {
                        fillColor: function (rowIndex) {
                            if (rowIndex === 0) { return '#d3d6d9'; }
                            return rowIndex % 2 === 0 ? '#fafbfc' : null;
                        },
                        hLineWidth: function () { return 0.5; },
                        vLineWidth: function () { return 0.5; },
                        hLineColor: function () { return '#dee2e6'; },
                        vLineColor: function () { return '#dee2e6'; }
                    };
                }

                doc.footer = function (page, pages) {
                    return {
                        columns: [
                            { text: brandLine2 + ' — ' + exportTitle + ' Report', fontSize: 7, color: '#666', margin: [22, 6, 0, 0] },
                            { text: page + ' / ' + pages, fontSize: 7, color: '#666', alignment: 'right', margin: [0, 6, 22, 0] }
                        ]
                    };
                };
            } catch (e) {
            }
        }

        try {
            new $.fn.dataTable.Buttons(api, {
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-download me-1"></i>Export',
                        className: 'btn btn-sm dtb-btn dtb-export',
                        autoClose: true,
                        buttons: [
                            { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel me-2"></i>Excel (.xlsx)', title: exportTitle, exportOptions: sharedExportOptions },
                            { extend: 'csvHtml5',   text: '<i class="bi bi-filetype-csv me-2"></i>CSV (.csv)',   title: exportTitle, exportOptions: sharedExportOptions },
                            { extend: 'pdfHtml5',   text: '<i class="bi bi-file-earmark-pdf me-2"></i>PDF (.pdf)',   title: exportTitle + ' Report', orientation: 'landscape', pageSize: 'A4', exportOptions: sharedExportOptions, customize: customizePdf }
                        ]
                    },
                    {
                        extend: 'print',
                        text: '<i class="bi bi-printer me-1"></i>Print',
                        className: 'btn btn-sm dtb-btn dtb-print',
                        title: '',
                        exportOptions: sharedExportOptions,
                        customize: customizePrint
                    }
                ]
            });
            api.buttons().container().appendTo($toolbar.find('.dt-toolbar-right'));
        } catch (e) {
        }
    }
});
</script>
@endpush
