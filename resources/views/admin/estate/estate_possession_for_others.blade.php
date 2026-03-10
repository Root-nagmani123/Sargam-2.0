@extends('admin.layouts.master')

@section('title', 'Estate Possession for Other - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Estate Possession for Other"></x-breadcrum>

    <!-- Page Card - Bootstrap 5 -->
    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5" id="possessionCardBody">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4 no-print">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Estate Possession for Other</h1>
                    <p class="text-muted small mb-0">This page displays all Possession added in the system, and provides options to manage records such as add, edit, delete, excel upload, excel download, print etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('admin.estate.update-meter-reading-of-other') }}" id="btnUpdateReading" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2 text-decoration-none">
                        <i class="bi bi-speedometer2"></i>
                        <span>Update Reading</span>
                    </a>
                    <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" title="Add possession">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add</span>
                    </a>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">
                            <i class="bi bi-columns-gap"></i>
                            <span class="d-none d-md-inline ms-1">Show / hide columns</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="columnToggleMenu"></ul>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnPrint" title="Print">
                        <i class="bi bi-printer"></i>
                        <span class="d-none d-md-inline">Print</span>
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('success') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('error') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <hr class="my-4">
            <div class="estate-possession-table-wrapper table-responsive overflow-auto rounded-3">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover align-middle text-nowrap mb-0 w-100', 'style' => 'min-width: 1200px;', 'id' => 'estatePossessionTable', 'aria-describedby' => 'estate-possession-caption']) !!}
            </div>
            <div id="estate-possession-caption" class="visually-hidden">Estate Possession for Others list</div>
        </div>
    </div>
</div>

<!-- Delete modal removed: possession delete disabled on UI -->
@endsection

@push('styles')
<style>
    /* Bootstrap 5 DataTables styling */
    #estatePossessionTable_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #estatePossessionTable_wrapper .dataTables_length select {
        width: auto;
        min-width: 4.5rem;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color, #dee2e6);
    }
    #estatePossessionTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    #estatePossessionTable_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    #estatePossessionTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem;
        white-space: nowrap;
    }
    #estatePossessionTable_wrapper tbody tr:nth-of-type(odd) {
        background-color: rgba(13, 110, 253, 0.05);
    }
    #estatePossessionTable_wrapper .dataTables_paginate .page-link {
        border-radius: var(--bs-border-radius);
        padding: 0.25rem 0.5rem;
    }
    #estatePossessionTable_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        .no-print { display: none !important; }
        #estatePossessionTable_wrapper .dataTables_length,
        #estatePossessionTable_wrapper .dataTables_filter,
        #estatePossessionTable_wrapper .dataTables_paginate { display: none !important; }

        /* DataTables scrollX can clip columns on print; force full table rendering */
        .estate-possession-table-wrapper,
        #estatePossessionTable_wrapper .dataTables_scroll,
        #estatePossessionTable_wrapper .dataTables_scrollBody,
        #estatePossessionTable_wrapper .dataTables_scrollHead {
            overflow: visible !important;
        }
        #estatePossessionTable_wrapper .dataTables_scrollBody {
            height: auto !important;
            max-height: none !important;
        }
        /* Avoid duplicated header tables and ensure header prints nicely */
        #estatePossessionTable_wrapper .dataTables_scrollHead {
            display: none !important;
        }
        #estatePossessionTable_wrapper table,
        #estatePossessionTable_wrapper table.dataTable {
            width: 100% !important;
        }
        body {
            /* Fit wide tables in one page width */
            zoom: 0.78;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        #estatePossessionTable_wrapper th,
        #estatePossessionTable_wrapper td {
            white-space: normal !important;
            word-break: break-word;
            font-size: 11px;
            padding: 0.35rem 0.4rem !important;
        }
        #estatePossessionTable_wrapper thead { display: table-header-group; }
    }
    .estate-possession-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .estate-possession-table-wrapper,
        #estatePossessionTable_wrapper .dataTables_scrollBody {
            max-height: 60vh;
            overflow-y: auto !important;
        }
    }
</style>
@endpush

@push('scripts')
    @include('admin.estate.partials.lbsnaa_print_layout')
    {!! $dataTable->scripts() !!}
    <script>
    $(document).ready(function() {
        var table = $('#estatePossessionTable').DataTable();

        // Column visibility toggle (build menu from table columns, skip checkbox and actions)
        function buildColumnToggle() {
            var menu = $('#columnToggleMenu');
            menu.empty();
            table.columns().every(function(i) {
                var col = this;
                var header = $(col.header()).text().trim();
                if (!header || header === 'Actions') return;
                var $li = $('<li><label class="dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-0"><input type="checkbox" class="form-check-input column-toggle" data-column="' + i + '"> ' + header + '</label></li>');
                $li.find('input').prop('checked', col.visible());
                menu.append($li);
            });
        }
        $(document).on('change', '.column-toggle', function() {
            var colIdx = $(this).data('column');
            table.column(colIdx).visible($(this).prop('checked'));
        });
        table.on('draw', function() { buildColumnToggle(); });
        buildColumnToggle();

        function buildPrintableTableHtml() {
            var visibleIndexes = [];
            table.columns().every(function(i) {
                var header = ($(this.header()).text() || '').trim();
                if (!header || header.toLowerCase() === 'actions') return;
                if (this.visible()) visibleIndexes.push(i);
            });

            var html = '<table class="table table-bordered table-striped">';
            html += '<thead><tr>';
            visibleIndexes.forEach(function(colIdx) {
                var h = ($(table.column(colIdx).header()).text() || '').trim();
                html += '<th>' + h + '</th>';
            });
            html += '</tr></thead><tbody>';

            table.rows({ search: 'applied' }).nodes().each(function(rowNode) {
                var $row = $(rowNode);
                if ($row.hasClass('child')) return;
                html += '<tr>';
                visibleIndexes.forEach(function(colIdx) {
                    var cellNode = table.cell(rowNode, colIdx).node();
                    var cellHtml = '';
                    if (cellNode) {
                        var $cell = $(cellNode).clone();
                        $cell.find('input, button, select, textarea').remove();
                        $cell.find('a.btn, .btn, .form-check-input').remove();
                        cellHtml = ($cell.html() || '').trim();
                    }
                    html += '<td>' + cellHtml + '</td>';
                });
                html += '</tr>';
            });

            html += '</tbody></table>';
            return html;
        }

        function openPrintWindow(tableHtml) {
            var title = 'Estate Possession for Other';
            var win = window.open('', '_blank');
            if (!win) {
                window.print();
                return;
            }
            var docHtml = (window.LBSNAAPrint && window.LBSNAAPrint.getDocumentHtml)
                ? window.LBSNAAPrint.getDocumentHtml(title, tableHtml)
                : '<!doctype html><html><head><meta charset="utf-8"><title>' + title + '</title></head><body><h2>' + title + '</h2>' + tableHtml + '</body></html>';
            win.document.open();
            win.document.write(docHtml);
            win.document.close();
            win.onafterprint = function() { win.close(); };
            setTimeout(function() { win.focus(); win.print(); }, 250);
        }

        $('#btnPrint').on('click', function() {
            if (!table) {
                window.print();
                return;
            }

            // Print should include ALL rows while respecting current show/hide column state.
            var originalLen = table.page.len();
            var originalPage = table.page();

            var restore = function() {
                table.page.len(originalLen);
                table.page(originalPage);
                table.draw(false);
            };

            // Ensure restore runs once even if onafterprint doesn't fire consistently.
            var restored = false;
            var safeRestore = function() {
                if (restored) return;
                restored = true;
                restore();
            };

            // Load all rows for printing (DataTables "All").
            table.one('draw', function() {
                setTimeout(function() {
                    var tableHtml = buildPrintableTableHtml();
                    openPrintWindow(tableHtml);
                    // Restore after we trigger the print window.
                    setTimeout(safeRestore, 800);
                }, 250);
            });

            table.page.len(-1).draw();
        });

        $('#btnUpdateReading').on('click', function(e) {
            e.preventDefault();
            var ids = [];
            $('#estatePossessionTable .row-select-possession:checked').each(function() {
                var id = $(this).data('id');
                if (id) ids.push(id);
            });
            if (ids.length !== 1) {
                alert('Please select exactly one member to update reading.');
                return;
            }
            var baseUrl = "{{ route('admin.estate.update-meter-reading-of-other') }}";
            window.location = baseUrl + '?possession_pks=' + ids[0];
        });

    });
    </script>
@endpush
