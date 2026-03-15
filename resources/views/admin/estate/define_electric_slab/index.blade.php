@extends('admin.layouts.master')

@section('title', 'Define Electric Slab - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Define Electric Slab" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row flex-md-nowrap justify-content-between align-items-start align-items-md-center gap-3 mb-4 no-print">
                <div class="flex-grow-1">
                    <h1 class="h4 fw-bold text-dark mb-1">Define Electric Slab</h1>
                    <p class="text-muted small mb-0">This page displays all electric slab settings in the system and provides options to manage records such as add, edit, delete etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('admin.estate.define-electric-slab.create') }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" title="Add">
                        <i class="material-icons material-symbols-rounded">add</i>
                        <span>Add</span>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2" id="btnElectricSlabPrint" title="Print">
                        <i class="material-icons material-symbols-rounded">print</i>
                        <span>Print</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2" id="btnElectricSlabExport" title="Export to Excel">
                        <i class="material-icons material-symbols-rounded">file_download</i>
                        <span>Export</span>
                    </button>
                </div>
            </div>

            <div class="electric-slab-table-wrapper table-responsive">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0',
                    'aria-describedby' => 'electric-slab-caption'
                ]) !!}
            </div>
            <div id="electric-slab-caption" class="visually-hidden">Define Electric Slab list</div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 8mm;
    }
    .no-print { display: none !important; }
    #electricSlabTable_wrapper .dataTables_length,
    #electricSlabTable_wrapper .dataTables_filter,
    #electricSlabTable_wrapper .dataTables_paginate { display: none !important; }

    .electric-slab-table-wrapper,
    #electricSlabTable_wrapper .dataTables_scroll,
    #electricSlabTable_wrapper .dataTables_scrollBody,
    #electricSlabTable_wrapper .dataTables_scrollHead {
        overflow: visible !important;
    }
    #electricSlabTable_wrapper .dataTables_scrollBody {
        height: auto !important;
        max-height: none !important;
    }
    #electricSlabTable_wrapper .dataTables_scrollHead {
        display: none !important;
    }
    #electricSlabTable_wrapper table,
    #electricSlabTable_wrapper table.dataTable {
        width: 100% !important;
    }
    body {
        zoom: 0.78;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    #electricSlabTable_wrapper th,
    #electricSlabTable_wrapper td {
        white-space: normal !important;
        word-break: break-word;
        font-size: 11px;
        padding: 0.35rem 0.4rem !important;
    }
    #electricSlabTable_wrapper thead { display: table-header-group; }
}
.electric-slab-table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
    <script>
    (function() {
        var slabTableApi = null;

        $(document).on('init.dt', function(e, settings) {
            if (settings.nTable && settings.nTable.id === 'electricSlabTable') {
                slabTableApi = new $.fn.dataTable.Api(settings);
            }
        });

        function buildPrintableHtml() {
            if (!slabTableApi) return '';
            var table = slabTableApi;
            var visibleIndexes = [];

            table.columns().every(function(i) {
                var header = ($(this.header()).text() || '').trim();
                if (!header || header.toUpperCase() === 'EDIT') return;
                visibleIndexes.push(i);
            });

            var html = '<table class="table table-bordered table-striped">';
            html += '<thead><tr>';
            visibleIndexes.forEach(function(colIdx) {
                var h = ($(table.column(colIdx).header()).text() || '').trim();
                html += '<th>' + h + '</th>';
            });
            html += '</tr></thead><tbody>';

            table.rows({ search: 'applied' }).every(function() {
                var rowNode = this.node();
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
                        cellHtml = ($cell.text() || '').trim();
                    }
                    html += '<td>' + cellHtml + '</td>';
                });
                html += '</tr>';
            });

            html += '</tbody></table>';
            return html;
        }

        function openPrintWindow(tableHtml) {
            var title = 'Define Electric Slab';
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

        function csvEscape(val) {
            var v = (val || '').replace(/"/g, '""');
            return '"' + v + '"';
        }

        function buildCsv() {
            if (!slabTableApi) return '';
            var table = slabTableApi;
            var visibleIndexes = [];
            var headers = [];

            table.columns().every(function(i) {
                var header = ($(this.header()).text() || '').trim();
                if (!header || header.toUpperCase() === 'EDIT') return;
                visibleIndexes.push(i);
                headers.push(header);
            });

            var lines = [];
            lines.push(headers.map(csvEscape).join(','));

            table.rows({ search: 'applied' }).every(function() {
                var rowNode = this.node();
                var $row = $(rowNode);
                if ($row.hasClass('child')) return;
                var cells = [];
                visibleIndexes.forEach(function(colIdx) {
                    var cellNode = table.cell(rowNode, colIdx).node();
                    var text = '';
                    if (cellNode) {
                        text = $(cellNode).text().trim().replace(/\s+/g, ' ');
                    }
                    cells.push(csvEscape(text));
                });
                lines.push(cells.join(','));
            });

            return lines.join('\n');
        }

        $('#btnElectricSlabPrint').on('click', function() {
            if (!slabTableApi) {
                window.print();
                return;
            }

            var table = slabTableApi;
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
                    var html = buildPrintableHtml();
                    openPrintWindow(html);
                    setTimeout(safeRestore, 800);
                }, 250);
            });

            table.page.len(-1).draw();
        });

        $('#btnElectricSlabExport').on('click', function() {
            if (!slabTableApi) return;

            var table = slabTableApi;
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
                    var csv = buildCsv();
                    var BOM = '\uFEFF';
                    var blob = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });
                    var link = document.createElement('a');
                    var url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', 'EstateElectricSlab_' + (new Date().toISOString().slice(0,10)) + '.csv');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    setTimeout(safeRestore, 500);
                }, 250);
            });

            table.page.len(-1).draw();
        });
    })();
    </script>
@endpush
@endsection
