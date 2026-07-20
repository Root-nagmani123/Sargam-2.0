@extends('admin.layouts.master')

@section('title', 'Vehicle Pass Request - Sargam')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid vehicle-pass-index-page">
    <x-breadcrum title="Vehicle Pass Request">
        <a href="{{ route('admin.security.vehicle_pass.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Request for Vehicle Pass</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Filter vehicle passes by status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        id="vpFilterActive" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="vpFilterArchive" aria-pressed="false">Archive</button>
            </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="vpPrintBtn" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
            <div class="dropdown">
                <button type="button" class="btn programme-dt-btn-columns dropdown-toggle border-0 text-primary" id="vpDownloadBtn"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="vpDownloadBtn">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="vpExportPdf">
                            <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> Download PDF
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="vpExportExcel">
                            <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i> Download Excel
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="vpBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#vpColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="vpDtSearch" class="programme-dt-search" data-dt-search-for="vehiclePass-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="vpDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="vehiclePass-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="vpColumnVisibilityModal" tabindex="-1" aria-labelledby="vpColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="vpColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="vpColumnToggleGrid"></div>
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
        var TABLE_ID = '#vehiclePass-table';
        var table;
        var currentFilter = 'active';

        function enhanceVpDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#vpDtSearch');
            var $footer = $('#vpDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search vehicle passes');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) {
                updateVpDtCount();
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
            updateVpDtCount();
        }

        function updateVpDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#vpDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        var vpColStorageKey = 'vehiclePassGrid:hiddenColumns:v1';

        function vpGetHiddenCols() {
            try {
                var raw = localStorage.getItem(vpColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function vpPersistHiddenCols(arr) {
            try { localStorage.setItem(vpColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupVpColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = vpGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#vpColumnToggleGrid');
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

                var inputId = 'vpcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = vpGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    vpPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        function setVpActivePill($btn) {
            $('#vpFilterActive, #vpFilterArchive')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $btn.addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');
        }

        // Send the current pill as status_filter on every ajax load.
        $(document).on('preXhr.dt', TABLE_ID, function (e, settings, data) {
            data.status_filter = currentFilter;
        });

        $('#vpFilterActive').on('click', function () {
            if (currentFilter === 'active') return;
            setVpActivePill($(this));
            currentFilter = 'active';
            if (table) table.ajax.reload();
        });

        $('#vpFilterArchive').on('click', function () {
            if (currentFilter === 'archive') return;
            setVpActivePill($(this));
            currentFilter = 'archive';
            if (table) table.ajax.reload();
        });

        /* ---- Branded Print / PDF / Excel exports (honour the pill + search) ---- */
        var vpExportUrl = @json(route('admin.security.vehicle_pass.export'));

        function buildVpExportUrl(format) {
            var url = vpExportUrl + '?format=' + encodeURIComponent(format) + '&tab=' + encodeURIComponent(currentFilter);
            var term = table ? table.search() : '';
            if (term) {
                url += '&search=' + encodeURIComponent(term);
            }
            return url;
        }

        $('#vpPrintBtn').on('click', function () {
            window.open(buildVpExportUrl('print'), '_blank');
        });
        $('#vpExportPdf').on('click', function () {
            window.location = buildVpExportUrl('pdf');
        });
        $('#vpExportExcel').on('click', function () {
            window.location = buildVpExportUrl('excel');
        });

        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            enhanceVpDtControls();
            updateVpDtCount();
            setupVpColumns(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#vpDtFooter .dataTables_paginate').length) {
                    $('#vpDtFooter').empty().data('dtReady', false);
                    enhanceVpDtControls();
                }
                updateVpDtCount();
            });

            setTimeout(function () {
                enhanceVpDtControls();
                updateVpDtCount();
            }, 300);
        }, 150);
    });
</script>
@endpush
