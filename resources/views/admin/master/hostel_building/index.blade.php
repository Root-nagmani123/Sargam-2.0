@extends('admin.layouts.master')

@section('title', 'Hostel Building')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid hostel-building-page">
    <x-breadcrum title="Building Master">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="hbAddBtn" data-bs-toggle="modal" data-bs-target="#hbFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Hostel Building</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Print / Download) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <button type="button" class="btn programme-dt-btn-columns" id="hbPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
        <a href="{{ route('master.hostel.building.export') }}" class="btn programme-dt-btn-columns" title="Download">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="hbBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#hbColumnVisibilityModal"
                        title="Show / hide columns" style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="hbDtSearch" class="programme-dt-search" data-dt-search-for="hostelbuildingmaster-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="hbDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="hostelbuildingmaster-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Hostel Building Modal -->
<div class="modal fade" id="hbFormModal" tabindex="-1" aria-labelledby="hbFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="hbBuildingForm" action="{{ route('master.hostel.building.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="hbPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="hbFormModalLabel">Add Hostel Building</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="hbFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="hbBuildingName" class="form-label fw-semibold">Building Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hbBuildingName" name="building_name"
                               placeholder="eg. Naramada Hostel" maxlength="255" required>
                        <div class="invalid-feedback" data-field="building_name"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hbFloors" class="form-label fw-semibold">Number of Floors <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="hbFloors" name="no_of_floors"
                               placeholder="eg. 25" min="0" required>
                        <div class="invalid-feedback" data-field="no_of_floors"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hbRooms" class="form-label fw-semibold">Number of Rooms <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="hbRooms" name="no_of_rooms"
                               placeholder="eg. 24" min="0" required>
                        <div class="invalid-feedback" data-field="no_of_rooms"></div>
                    </div>

                    <div class="mb-3">
                        <label for="hbType" class="form-label fw-semibold">Building Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="hbType" name="building_type" required>
                            <option value="">Select Type</option>
                            @foreach(($buildingType ?? []) as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="building_type"></div>
                    </div>

                    <div class="mb-0">
                        <label for="hbStatus" class="form-label fw-semibold">Building Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="hbStatus" name="active_inactive" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="invalid-feedback" data-field="active_inactive"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="hbSubmitBtn">Add Hostel Building</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="hbColumnVisibilityModal" tabindex="-1" aria-labelledby="hbColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="hbColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="hbColumnToggleGrid"></div>
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
        var TABLE_ID = '#hostelbuildingmaster-table';
        var table;

        /* ---- Relocate search + build footer (pagination + count) ---- */
        function enhanceHbDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#hbDtSearch');
            var $footer = $('#hbDtFooter');

            // Search → toolbar right
            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search buildings');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            // Footer: pagination + count (once)
            if ($footer.data('dtReady')) {
                updateHbDtCount();
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
                var $select = $length.find('select').addClass('form-select form-select-sm');
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
            updateHbDtCount();
        }

        function updateHbDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#hbDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        /* ---- Column show / hide (DataTables API) ---- */
        var hbColStorageKey = 'hbGrid:hiddenColumns:v1';

        function hbGetHiddenCols() {
            try {
                var raw = localStorage.getItem(hbColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function hbPersistHiddenCols(arr) {
            try { localStorage.setItem(hbColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupHbColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = hbGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#hbColumnToggleGrid');
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

                var inputId = 'hbcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = hbGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    hbPersistHiddenCols(h);
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

            enhanceHbDtControls();
            updateHbDtCount();
            setupHbColumns(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#hbDtFooter .dataTables_paginate').length) {
                    $('#hbDtFooter').empty().data('dtReady', false);
                    enhanceHbDtControls();
                }
                updateHbDtCount();
            });

            setTimeout(function () {
                enhanceHbDtControls();
                updateHbDtCount();
            }, 300);
        }, 150);

        /* ---- Print ---- */
        $('#hbPrintBtn').on('click', function () {
            window.print();
        });

        /* ---- Add / Edit modal ---- */
        var $form = $('#hbBuildingForm');
        var $alert = $('#hbFormAlert');

        function hbClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function hbResetForm() {
            $form[0].reset();
            $('#hbPk').val('');
            hbClearErrors();
        }

        // Open for "Add"
        $('#hbAddBtn').on('click', function () {
            hbResetForm();
            $('#hbFormModalLabel').text('Add Hostel Building');
            $('#hbSubmitBtn').text('Add Hostel Building');
            $('#hbStatus').val('1'); // sensible default for new records
        });

        // Open for "Edit" (populate from row data attributes)
        $(document).on('click', '#hostelbuildingmaster-table .hb-edit-btn', function () {
            var $btn = $(this);
            hbResetForm();
            $('#hbFormModalLabel').text('Edit Hostel Building');
            $('#hbSubmitBtn').text('Update');

            $('#hbPk').val($btn.data('id'));
            $('#hbBuildingName').val($btn.data('name'));
            $('#hbFloors').val($btn.data('floors'));
            $('#hbRooms').val($btn.data('rooms'));
            $('#hbType').val(String($btn.data('type')));
            $('#hbStatus').val(String($btn.data('status')));

            var modalEl = document.getElementById('hbFormModal');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        // AJAX submit (create + update share the store route)
        $form.on('submit', function (e) {
            e.preventDefault();
            hbClearErrors();

            var $submit = $('#hbSubmitBtn');
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

                    if ($.fn.DataTable.isDataTable(TABLE_ID)) {
                        $(TABLE_ID).DataTable().ajax.reload(null, false);
                    }

                    setTimeout(function () {
                        bootstrap.Modal.getInstance(document.getElementById('hbFormModal'))?.hide();
                        hbResetForm();
                    }, 1000);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            var $input = $form.find('[name="' + field + '"]');
                            $input.addClass('is-invalid');
                            $form.find('.invalid-feedback[data-field="' + field + '"]').text(errors[field][0]);
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'An error occurred while saving. Please try again.';
                        $alert.removeClass('d-none alert-success').addClass('alert-danger')
                              .html('<i class="bi bi-exclamation-circle me-1"></i>' + msg);
                    }
                },
                complete: function () {
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        });

        // Reset the form whenever the modal closes (so a stale edit can't leak into Add)
        document.getElementById('hbFormModal').addEventListener('hidden.bs.modal', function () {
            hbResetForm();
            $('#hbFormModalLabel').text('Add Hostel Building');
            $('#hbSubmitBtn').text('Add Hostel Building');
        });
    });
</script>
@endpush
