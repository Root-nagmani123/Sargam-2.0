@extends('admin.layouts.master')

@section('title', 'Vehicle Pass Configuration - Security Management')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid vehicle-pass-config-page">
    <x-breadcrum title="Vehicle Pass Configuration">
        <a href="{{ route('admin.security.vehicle_pass_config.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
           id="openCreateConfig">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Configuration</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="vpcBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#vpcColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="vpcDtSearch" class="programme-dt-search" data-dt-search-for="vehiclePassConfig-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="vpcDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="vehiclePassConfig-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Configuration Modal (form loaded via AJAX) -->
<div class="modal fade" id="vehiclePassConfigModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden" id="vehiclePassConfigModalContent">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="vpcColumnVisibilityModal" tabindex="-1" aria-labelledby="vpcColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="vpcColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="vpcColumnToggleGrid"></div>
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
        var TABLE_ID = '#vehiclePassConfig-table';
        var table;

        function enhanceVpcDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#vpcDtSearch');
            var $footer = $('#vpcDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search configurations');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) {
                updateVpcDtCount();
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
            updateVpcDtCount();
        }

        function updateVpcDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#vpcDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        var vpcColStorageKey = 'vehiclePassConfigGrid:hiddenColumns:v1';

        function vpcGetHiddenCols() {
            try {
                var raw = localStorage.getItem(vpcColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function vpcPersistHiddenCols(arr) {
            try { localStorage.setItem(vpcColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupVpcColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = vpcGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#vpcColumnToggleGrid');
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

                var inputId = 'vpccolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = vpcGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    vpcPersistHiddenCols(h);
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

            enhanceVpcDtControls();
            updateVpcDtCount();
            setupVpcColumns(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#vpcDtFooter .dataTables_paginate').length) {
                    $('#vpcDtFooter').empty().data('dtReady', false);
                    enhanceVpcDtControls();
                }
                updateVpcDtCount();
            });

            setTimeout(function () {
                enhanceVpcDtControls();
                updateVpcDtCount();
            }, 300);
        }, 150);

        /* ---- Add / Edit via AJAX modal ---- */
        function openConfigModal(url) {
            $.get(url, function (data) {
                $('#vehiclePassConfigModalContent').html(data);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('vehiclePassConfigModal')).show();
            });
        }

        $('#openCreateConfig').on('click', function (e) {
            e.preventDefault();
            openConfigModal($(this).attr('href'));
        });

        $(document).on('click', '#vehiclePassConfig-table .openEditConfig', function (e) {
            e.preventDefault();
            openConfigModal($(this).attr('href'));
        });

        /* ---- Status toggle (dedicated route + table reload) ---- */
        $(document).on('change', '#vehiclePassConfig-table .config-status-toggle', function () {
            var $cb = $(this);
            var url = $cb.data('url');
            var isChecked = $cb.is(':checked');

            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), status: isChecked ? 1 : 0 },
                success: function (response) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success((response && response.message) || 'Status updated successfully');
                    }
                    if ($.fn.DataTable.isDataTable('#vehiclePassConfig-table')) {
                        $('#vehiclePassConfig-table').DataTable().ajax.reload(null, false);
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
