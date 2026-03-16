@extends('admin.layouts.master')

@section('title', 'Possession Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Possession Details"></x-breadcrum>
    <x-estate-workflow-stepper current="possession-details" />

    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5" id="possessionDetailsCardBody">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4 no-print">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Possession Details</h1>
                    <p class="text-muted small mb-0">LBSNAA employee possession records (allotted via HAC Approved flow).</p>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('admin.estate.possession-details.create') }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" title="Add possession">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add</span>
                    </a>
                    @if(hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))
                    <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-right-circle"></i>
                        <span>Update Reading</span>
                    </a>
                    @endif
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
            <div class="estate-possession-details-table-wrapper table-responsive overflow-auto rounded-3">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover align-middle text-nowrap mb-0 w-100', 'style' => 'min-width: 1200px;', 'id' => 'estatePossessionDetailsTable', 'aria-describedby' => 'estate-possession-details-caption']) !!}
            </div>
            <div id="estate-possession-details-caption" class="visually-hidden">Possession details list</div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deletePossessionDetailsModal" tabindex="-1" aria-labelledby="deletePossessionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="deletePossessionDetailsModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0">Are you sure you want to delete this possession details record? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-2 d-inline-flex align-items-center gap-2" id="confirmDeletePossessionDetailsBtn">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #estatePossessionDetailsTable_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #estatePossessionDetailsTable_wrapper .dataTables_length select {
        width: auto;
        min-width: 4.5rem;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color, #dee2e6);
    }
    #estatePossessionDetailsTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    #estatePossessionDetailsTable_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    #estatePossessionDetailsTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem;
        white-space: nowrap;
    }
    #estatePossessionDetailsTable_wrapper tbody tr:nth-of-type(odd) {
        background-color: rgba(13, 110, 253, 0.05);
    }
    #estatePossessionDetailsTable_wrapper .dataTables_paginate .page-link {
        border-radius: var(--bs-border-radius);
        padding: 0.25rem 0.5rem;
    }
    #estatePossessionDetailsTable_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        .no-print { display: none !important; }
        #estatePossessionDetailsTable_wrapper .dataTables_length,
        #estatePossessionDetailsTable_wrapper .dataTables_filter,
        #estatePossessionDetailsTable_wrapper .dataTables_paginate { display: none !important; }

        .estate-possession-details-table-wrapper,
        #estatePossessionDetailsTable_wrapper .dataTables_scroll,
        #estatePossessionDetailsTable_wrapper .dataTables_scrollBody,
        #estatePossessionDetailsTable_wrapper .dataTables_scrollHead {
            overflow: visible !important;
        }
        #estatePossessionDetailsTable_wrapper .dataTables_scrollBody {
            height: auto !important;
            max-height: none !important;
        }
        #estatePossessionDetailsTable_wrapper .dataTables_scrollHead {
            display: none !important;
        }
        #estatePossessionDetailsTable_wrapper table,
        #estatePossessionDetailsTable_wrapper table.dataTable {
            width: 100% !important;
        }
        body {
            zoom: 0.78;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        #estatePossessionDetailsTable_wrapper th,
        #estatePossessionDetailsTable_wrapper td {
            white-space: normal !important;
            word-break: break-word;
            font-size: 11px;
            padding: 0.35rem 0.4rem !important;
        }
        #estatePossessionDetailsTable_wrapper thead { display: table-header-group; }
    }
    .estate-possession-details-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .estate-possession-details-table-wrapper,
        #estatePossessionDetailsTable_wrapper .dataTables_scrollBody {
            max-height: 60vh;
            overflow-y: auto !important;
        }
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(document).ready(function() {
        var table = $('#estatePossessionDetailsTable').DataTable();

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
            var title = 'Possession Details';
            var win = window.open('', '_blank');
            if (!win) {
                window.print();
                return;
            }

            win.document.open();
            win.document.write(
                '<!doctype html><html><head><meta charset="utf-8">' +
                '<title>' + title + '</title>' +
                '<style>' +
                '@page{size:A4 landscape;margin:8mm;}' +
                'body{font-family:Arial, sans-serif;font-size:11px;color:#111;}' +
                'h2{margin:0 0 8px 0;font-size:14px;}' +
                'table{width:100%;border-collapse:collapse;}' +
                'th,td{border:1px solid #333;padding:4px 6px;vertical-align:top;word-break:break-word;white-space:normal;}' +
                'thead{display:table-header-group;}' +
                'tr{page-break-inside:avoid;}' +
                '</style></head><body>' +
                '<h2>' + title + '</h2>' +
                tableHtml +
                '</body></html>'
            );
            win.document.close();

            setTimeout(function() {
                win.focus();
                win.print();
                win.close();
            }, 250);
        }

        $('#btnPrint').on('click', function() {
            if (!table) {
                window.print();
                return;
            }

            var originalLen = table.page.len();
            var originalPage = table.page();

            var restore = function() {
                table.page.len(originalLen);
                table.page(originalPage);
                table.draw(false);
            };

            var restored = false;
            var safeRestore = function() {
                if (restored) return;
                restored = true;
                restore();
            };

            table.one('draw', function() {
                setTimeout(function() {
                    var tableHtml = buildPrintableTableHtml();
                    openPrintWindow(tableHtml);
                    setTimeout(safeRestore, 800);
                }, 250);
            });

            table.page.len(-1).draw();
        });
    });
    </script>
@endpush
