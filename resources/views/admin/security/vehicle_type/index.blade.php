@extends('admin.layouts.master')

@section('title', 'Vehicle Types - Security Management')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid vehicle-type-page">
    <x-breadcrum title="Vehicle Types">
        <a href="{{ route('admin.security.vehicle_type.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
           id="openCreateVehicleType">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Vehicle Type</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="vtBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#vtColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="vtDtSearch" class="programme-dt-search" data-dt-search-for="vehicleType-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="vtDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="vehicleType-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Vehicle Type Modal (form loaded via AJAX) -->
<div class="modal fade" id="vehicleTypeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden" id="vehicleTypeModalContent">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="vtColumnVisibilityModal" tabindex="-1" aria-labelledby="vtColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="vtColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="vtColumnToggleGrid"></div>
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
        var TABLE_ID = '#vehicleType-table';
        var table;

        function enhanceVtDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#vtDtSearch');
            var $footer = $('#vtDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search vehicle types');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) {
                updateVtDtCount();
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
            updateVtDtCount();
        }

        function updateVtDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#vtDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        var vtColStorageKey = 'vehicleTypeGrid:hiddenColumns:v1';

        function vtGetHiddenCols() {
            try {
                var raw = localStorage.getItem(vtColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function vtPersistHiddenCols(arr) {
            try { localStorage.setItem(vtColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupVtColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = vtGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#vtColumnToggleGrid');
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

                var inputId = 'vtcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = vtGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    vtPersistHiddenCols(h);
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

            enhanceVtDtControls();
            updateVtDtCount();
            setupVtColumns(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#vtDtFooter .dataTables_paginate').length) {
                    $('#vtDtFooter').empty().data('dtReady', false);
                    enhanceVtDtControls();
                }
                updateVtDtCount();
            });

            setTimeout(function () {
                enhanceVtDtControls();
                updateVtDtCount();
            }, 300);
        }, 150);

        /* ---- Add / Edit via AJAX modal ---- */
        function openVehicleTypeModal(url) {
            $.get(url, function (data) {
                $('#vehicleTypeModalContent').html(data);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('vehicleTypeModal')).show();
            });
        }

        $('#openCreateVehicleType').on('click', function (e) {
            e.preventDefault();
            openVehicleTypeModal($(this).attr('href'));
        });

        $(document).on('click', '#vehicleType-table .openEditVehicleType', function (e) {
            e.preventDefault();
            openVehicleTypeModal($(this).attr('href'));
        });

        /* ---- Status toggle (dedicated route flips server-side + table reload) ---- */
        $(document).on('change', '#vehicleType-table .vehicle-type-status-toggle', function () {
            var $cb = $(this);
            var url = $cb.data('url');
            var isChecked = $cb.is(':checked');

            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Status updated successfully');
                    }
                    if ($.fn.DataTable.isDataTable('#vehicleType-table')) {
                        $('#vehicleType-table').DataTable().ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    $cb.prop('checked', !isChecked);
                    if (typeof toastr !== 'undefined') {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error updating status');
                    }
                }
            });
        });
    });
</script>
@endpush
