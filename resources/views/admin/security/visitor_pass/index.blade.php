@extends('admin.layouts.master')

@section('title', 'Visitor Pass Management')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid visitor-pass-page">
    <x-breadcrum title="Visitor Pass Management">
        <a href="{{ route('admin.security.visitor_pass.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Register New Visitor</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="vsBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#vsColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="vsDtSearch" class="programme-dt-search" data-dt-search-for="visitorPass-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="vsDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="visitorPass-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="vsColumnVisibilityModal" tabindex="-1" aria-labelledby="vsColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="vsColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="vsColumnToggleGrid"></div>
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
        var TABLE_ID = '#visitorPass-table';
        var table;

        function enhanceVsDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#vsDtSearch');
            var $footer = $('#vsDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search visitor passes');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) {
                updateVsDtCount();
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
            updateVsDtCount();
        }

        function updateVsDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#vsDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        var vsColStorageKey = 'visitorPassGrid:hiddenColumns:v1';

        function vsGetHiddenCols() {
            try {
                var raw = localStorage.getItem(vsColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function vsPersistHiddenCols(arr) {
            try { localStorage.setItem(vsColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupVsColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = vsGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#vsColumnToggleGrid');
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

                var inputId = 'vscolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = vsGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    vsPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            enhanceVsDtControls();
            updateVsDtCount();
            setupVsColumns(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#vsDtFooter .dataTables_paginate').length) {
                    $('#vsDtFooter').empty().data('dtReady', false);
                    enhanceVsDtControls();
                }
                updateVsDtCount();
            });

            setTimeout(function () {
                enhanceVsDtControls();
                updateVsDtCount();
            }, 300);
        }, 150);
    });
</script>
@endpush
