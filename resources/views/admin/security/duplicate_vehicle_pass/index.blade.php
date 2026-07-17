@extends('admin.layouts.master')

@section('title', 'Duplicate Vehicle Pass Request - Sargam')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid duplicate-vehicle-pass-page">
    <x-breadcrum title="Duplicate Vehicle Pass Request">
        <a href="{{ route('admin.security.duplicate_vehicle_pass.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add New Request</span>
        </a>
    </x-breadcrum>

    <x-session_message />
<div class="d-flex justify-content-end align-items-center gap-2 mb-3">
    <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="dvpPrintBtn" title="Print">
                        <i class="bi bi-printer" aria-hidden="true"></i>
                        <span>Print</span>
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn programme-dt-btn-columns dropdown-toggle border-0 text-primary" id="dvpDownloadBtn"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            <span>Download</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="dvpDownloadBtn">
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="dvpExportPdf">
                                    <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> Download PDF
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="dvpExportExcel">
                                    <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i> Download Excel
                                </button>
                            </li>
                        </ul>
                    </div>
</div>
    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="dvpBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#dvpColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="dvpDtSearch" class="programme-dt-search" data-dt-search-for="duplicateVehPass-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="dvpDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="duplicateVehPass-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="dvpColumnVisibilityModal" tabindex="-1" aria-labelledby="dvpColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="dvpColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="dvpColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(document).ready(function () {
        var TABLE_ID = '#duplicateVehPass-table';
        var table;

        /* ---- Relocate search + build footer (pagination + count) ---- */
        function enhanceDvpDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#dvpDtSearch');
            var $footer = $('#dvpDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search requests');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) {
                updateDvpDtCount();
                return;
            }

            var $paginate = $wrapper.find('.dataTables_paginate').first();
            var $length = $wrapper.find('.dataTables_length').first();
            var $info = $wrapper.find('.dataTables_info').first();

            if (!$footer.length || (!$paginate.length && !$length.length)) {
                return;
            }

            var $pagCol = $('<div class="programme-dt-pagination"></div>');
            var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

            if ($paginate.length) {
                $paginate.find('.pagination').addClass('mb-0');
                $pagCol.append($paginate);
            }

            if ($length.length) {
                var $select = $length.find('select').addClass('form-select form-select-sm').detach();
                $length.find('label')
                    .empty()
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
            updateDvpDtCount();
        }

        function updateDvpDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#dvpDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        /* ---- Column show / hide (DataTables API) ---- */
        var dvpColStorageKey = 'dvpGrid:hiddenColumns:v1';

        function dvpGetHiddenCols() {
            try {
                var raw = localStorage.getItem(dvpColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function dvpPersistHiddenCols(arr) {
            try { localStorage.setItem(dvpColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupDvpColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = dvpGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#dvpColumnToggleGrid');
            if (!$grid.length) {
                return;
            }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) {
                    return;
                }

                var inputId = 'dvpcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = dvpGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    dvpPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        /* ---- Branded Print / PDF / Excel exports (server-side, honour the search) ---- */
        function setupDvpExportButtons(dt) {
            if (!dt || dt.dvpExportReady) {
                return;
            }
            dt.dvpExportReady = true;

            var exportUrl = @json(route('admin.security.duplicate_vehicle_pass.export'));

            function buildUrl(format) {
                var url = exportUrl + '?format=' + encodeURIComponent(format);
                var term = dt.search();
                if (term) {
                    url += '&search=' + encodeURIComponent(term);
                }
                return url;
            }

            // Print opens a branded, auto-printing report in a new tab.
            $('#dvpPrintBtn').on('click', function () {
                window.open(buildUrl('print'), '_blank');
            });
            $('#dvpExportPdf').on('click', function () {
                window.location = buildUrl('pdf');
            });
            $('#dvpExportExcel').on('click', function () {
                window.location = buildUrl('excel');
            });
        }

        /* ---- Wait for Yajra DataTable init ---- */
        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            enhanceDvpDtControls();
            updateDvpDtCount();
            setupDvpColumns(table);
            setupDvpExportButtons(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#dvpDtFooter .dataTables_paginate').length) {
                    $('#dvpDtFooter').empty().data('dtReady', false);
                    enhanceDvpDtControls();
                }
                updateDvpDtCount();
            });

            setTimeout(function () {
                enhanceDvpDtControls();
                updateDvpDtCount();
            }, 300);
        }, 150);
    });
</script>
@endpush
