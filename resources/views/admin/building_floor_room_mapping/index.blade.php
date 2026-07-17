@extends('admin.layouts.master')

@section('title', 'Hostel Floor Room Map')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* Inline-editable comment: looks like text, editable on focus */
    .hostel-room-page .comment-input {
        border: 1px solid transparent;
        background: transparent;
        border-radius: 6px;
        padding: .35rem .5rem;
        font-size: .875rem;
        color: #344054;
        min-width: 140px;
    }
    .hostel-room-page .comment-input:hover {
        border-color: #e4e7ec;
        background: #fff;
    }
    .hostel-room-page .comment-input:focus {
        outline: 0;
        border-color: #004a93;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }
    .hostel-room-page .programme-dt-filter-select select { min-width: 150px; }
</style>
@endpush

@section('setup_content')
@php
    $currentQuery = request()->getQueryString();
    $exportUrl = route('hostel.building.floor.room.map.export') . ($currentQuery ? ('?' . $currentQuery) : '');
@endphp
<div class="container-fluid hostel-room-page">
    <x-breadcrum title="Hostel Floor Room Map">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="hrAddBtn" data-bs-toggle="modal" data-bs-target="#hrFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Hostel Floor Room</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Print / Download) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <button type="button" class="btn programme-dt-btn-columns" id="hrPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
        <a href="{{ $exportUrl }}" class="btn programme-dt-btn-columns" title="Download">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Filters + Columns + Search --}}
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <div class="programme-dt-filter-select">
                        <select id="hrBuildingFilter" class="form-select form-select-sm js-hr-filter" aria-label="Filter by building">
                            <option value="">Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="hrRoomTypeFilter" class="form-select form-select-sm js-hr-filter" aria-label="Filter by room type">
                            <option value="">Room Type</option>
                            @foreach($roomTypes as $key => $type)
                                <option value="{{ $key }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="hrStatusFilter" class="form-select form-select-sm js-hr-filter" aria-label="Filter by status">
                            <option value="">Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="hrResetFilters">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-xl-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="hrBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#hrColumnVisibilityModal" title="Show / hide columns" style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="hrDtSearch" class="programme-dt-search" data-dt-search-for="hostelbuildingfloorroommapping-table"></div>
                </div>
            </div>

            {{-- Table --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="hrDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="hostelbuildingfloorroommapping-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Hostel Floor Room Modal -->
<div class="modal fade" id="hrFormModal" tabindex="-1" aria-labelledby="hrFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="hrRoomForm" action="{{ route('hostel.building.floor.room.map.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="hrPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="hrFormModalLabel">Add Hostel Floor Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hrFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="hrBuilding" class="form-label fw-semibold">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="hrBuilding" name="building_master_pk" required>
                            <option value="">Select Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="building_master_pk"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hrFloor" class="form-label fw-semibold">Floor <span class="text-danger">*</span></label>
                        <select class="form-select" id="hrFloor" name="floor_master_pk" required>
                            <option value="">Select Floor</option>
                            @foreach($floors as $floor)
                                <option value="{{ $floor->pk }}">{{ $floor->floor_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="floor_master_pk"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hrRoomType" class="form-label fw-semibold">Room Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="hrRoomType" name="room_type" required>
                            <option value="">Select Type</option>
                            @foreach($roomTypes as $key => $type)
                                <option value="{{ $key }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="room_type"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hrRoomName" class="form-label fw-semibold">Room Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hrRoomName" name="room_name"
                               placeholder="eg. Naramada Hostel" maxlength="255" required>
                        <div class="invalid-feedback" data-field="room_name"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hrCapacity" class="form-label fw-semibold">Capacity of Room <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="hrCapacity" name="capacity"
                               placeholder="eg. 25" min="1" required>
                        <div class="invalid-feedback" data-field="capacity"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hrStatus" class="form-label fw-semibold">Building Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="hrStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>

                    <div class="mb-0">
                        <label for="hrComment" class="form-label fw-semibold">Comments</label>
                        <input type="text" class="form-control" id="hrComment" name="comment"
                               placeholder="eg. Lorem ipsum dolor sit amet" maxlength="255">
                        <div class="invalid-feedback" data-field="comment"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="hrSubmitBtn">Add Hostel Floor Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="hrColumnVisibilityModal" tabindex="-1" aria-labelledby="hrColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="hrColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="hrColumnToggleGrid"></div>
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
        var TABLE_ID = '#hostelbuildingfloorroommapping-table';
        var table;

        /* ---- Relocate search + build footer (pagination + count) ---- */
        function enhanceHrDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#hrDtSearch');
            var $footer = $('#hrDtFooter');

            // Search → toolbar right
            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search rooms');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            // Footer: pagination + count (once)
            if ($footer.data('dtReady')) {
                updateHrDtCount();
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
            updateHrDtCount();
        }

        function updateHrDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#hrDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        /* ---- Column show / hide (DataTables API) ---- */
        var hrColStorageKey = 'hrGrid:hiddenColumns:v1';

        function hrGetHiddenCols() {
            try {
                var raw = localStorage.getItem(hrColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function hrPersistHiddenCols(arr) {
            try { localStorage.setItem(hrColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupHrColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = hrGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#hrColumnToggleGrid');
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

                var inputId = 'hrcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = hrGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    hrPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        /* ---- Wait for Yajra DataTable init ---- */
        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            enhanceHrDtControls();
            updateHrDtCount();
            setupHrColumns(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#hrDtFooter .dataTables_paginate').length) {
                    $('#hrDtFooter').empty().data('dtReady', false);
                    enhanceHrDtControls();
                }
                updateHrDtCount();
            });

            setTimeout(function () {
                enhanceHrDtControls();
                updateHrDtCount();
            }, 300);
        }, 150);

        /* ---- Filters (reload the DataTable's ajax source) ---- */
        function hrReloadWithFilters() {
            if (!table) {
                return;
            }
            var params = {
                building_id: $('#hrBuildingFilter').val() || '',
                room_type: $('#hrRoomTypeFilter').val() || '',
                status: $('#hrStatusFilter').val() || ''
            };
            var url = new URL('{{ route('hostel.building.floor.room.map.index') }}');
            Object.keys(params).forEach(function (key) {
                if (params[key] !== '') {
                    url.searchParams.set(key, params[key]);
                }
            });
            table.ajax.url(url.toString()).load();
        }

        $('.js-hr-filter').on('change', hrReloadWithFilters);

        $('#hrResetFilters').on('click', function () {
            $('#hrBuildingFilter').val('');
            $('#hrRoomTypeFilter').val('');
            $('#hrStatusFilter').val('');
            hrReloadWithFilters();
        });

        /* ---- Print ---- */
        $('#hrPrintBtn').on('click', function () {
            window.print();
        });

        /* ---- Inline comment edit (unchanged behaviour) ---- */
        $(document).on('change', '.comment-input', function () {
            var id = $(this).data('id');
            var value = $(this).val();

            $.ajax({
                url: '{{ route("hostel.building.floor.room.map.update.comment") }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    id: id,
                    comment: value
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof toastr !== 'undefined') toastr.success('Comment updated successfully');
                    } else {
                        if (typeof toastr !== 'undefined') toastr.error('Failed to update comment');
                    }
                },
                error: function () {
                    if (typeof toastr !== 'undefined') toastr.error('Error occurred');
                }
            });
        });

        /* ---- Add / Edit modal ---- */
        var $form = $('#hrRoomForm');
        var $alert = $('#hrFormAlert');

        function hrClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function hrResetForm() {
            $form[0].reset();
            $('#hrPk').val('');
            hrClearErrors();
        }

        // Open for "Add"
        $('#hrAddBtn').on('click', function () {
            hrResetForm();
            $('#hrFormModalLabel').text('Add Hostel Floor Room');
            $('#hrSubmitBtn').text('Add Hostel Floor Room');
            $('#hrStatus').val('1');
        });

        // Open for "Edit"
        $(document).on('click', '.hr-edit-btn', function () {
            var $btn = $(this);
            hrResetForm();
            $('#hrFormModalLabel').text('Edit Hostel Floor Room');
            $('#hrSubmitBtn').text('Update');

            $('#hrPk').val($btn.data('id'));
            $('#hrBuilding').val(String($btn.data('building')));
            $('#hrFloor').val(String($btn.data('floor')));
            $('#hrRoomType').val(String($btn.data('roomtype')));
            $('#hrRoomName').val($btn.data('roomname'));
            $('#hrCapacity').val($btn.data('capacity'));
            $('#hrStatus').val(String($btn.data('status')));
            $('#hrComment').val($btn.data('comment'));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('hrFormModal')).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            hrClearErrors();

            var $submit = $('#hrSubmitBtn');
            var originalText = $submit.text();
            $submit.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    $alert.removeClass('d-none alert-danger').addClass('alert-success')
                          .html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));
                    setTimeout(function () { window.location.reload(); }, 800);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            $form.find('[name="' + field + '"]').addClass('is-invalid');
                            $form.find('.invalid-feedback[data-field="' + field + '"]').text(errors[field][0]);
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'An error occurred while saving. Please try again.';
                        $alert.removeClass('d-none alert-success').addClass('alert-danger')
                              .html('<i class="bi bi-exclamation-circle me-1"></i>' + msg);
                    }
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        });

        // Reset on close so a stale edit can't leak into Add
        document.getElementById('hrFormModal').addEventListener('hidden.bs.modal', function () {
            hrResetForm();
            $('#hrFormModalLabel').text('Add Hostel Floor Room');
            $('#hrSubmitBtn').text('Add Hostel Floor Room');
        });
    });
</script>
@endpush
