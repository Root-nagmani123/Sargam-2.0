@extends('admin.layouts.master')

@section('title', 'Holiday Master - Sargam | Lal Bahadur Shastri')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet"
    href="{{ asset('css/holiday-master-admin.css') }}?v={{ @filemtime(public_path('css/holiday-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid hm-page">
    <x-breadcrum title="Holiday Master" :showBack="false">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
            id="hmAddBtn" data-bs-toggle="modal" data-bs-target="#hmAddModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Holiday</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <p class="text-secondary small mb-3">
        Holidays saved here appear on the dashboard calendar and the course calendars.
        Only <strong>Active</strong> holidays are shown to users.
    </p>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter holidays by status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    data-hm-status="all" aria-pressed="true" aria-current="true">All</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    data-hm-status="active" aria-pressed="false">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    data-hm-status="inactive" aria-pressed="false">Inactive</button>
            </li>
        </ul>

        <button type="button" id="holidayDownload" class="btn hm-download-btn border-0">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select id="yearFilter" class="form-select" aria-label="Filter by year">
                            <option value="">Year</option>
                            @foreach ($years as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="typeFilter" class="form-select" aria-label="Filter by holiday type">
                            <option value="">Holiday Type</option>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hm-daterange">
                        <i class="bi bi-calendar3 hm-daterange__icon" aria-hidden="true"></i>
                        <input type="text" id="timePeriodFilter" class="form-control hm-daterange__input"
                            placeholder="Time Period" autocomplete="off" readonly
                            aria-label="Filter by holiday date range">
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                        Reset Filters
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnHolidayColumns"
                        data-bs-toggle="modal" data-bs-target="#holidayColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="holidayDtSearch" class="programme-dt-search"
                        data-dt-search-for="holiday-master-table"></div>
                </div>
            </div>

            <p class="small text-secondary d-lg-none mb-2" role="note">
                Scroll inside the table area to see all rows and columns.
            </p>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                        id="holiday-master-table">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Holiday Name</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Holiday Type</th>
                                <th>Year</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="holidayDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="holiday-master-table"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit modals — §3c. They deliberately share the same header / field card /
     control / footer language; only the contents and the submit caption differ. --}}

<!-- Add Holiday Modal -->
<div class="modal fade" id="hmAddModal" tabindex="-1" aria-labelledby="hmAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content hm-modal border-0 shadow">
            {{-- novalidate: the submit handler posts by XHR and renders the
                 controller's own 422 messages inline, so the browser must not
                 short-circuit it with its own bubbles first. --}}
            <form id="hmAddForm" method="POST" action="{{ route('admin.holiday-master.store') }}" novalidate>
                @csrf
                <div class="modal-header hm-modal-header">
                    <h5 class="modal-title" id="hmAddModalLabel">Add Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body hm-modal-body">
                    <div class="hm-field-card">
                        <div class="mb-3">
                            <label for="add_holiday_name" class="hm-form-label">Holiday Name<span class="hm-req">*</span></label>
                            <input type="text" class="form-control hm-control" id="add_holiday_name"
                                name="holiday_name" placeholder="e.g. Republic Day" maxlength="255" required>
                            <span class="hm-field-error" data-error-for="holiday_name"></span>
                        </div>
                        <div class="mb-3">
                            <label for="add_holiday_date" class="hm-form-label">Holiday Date<span class="hm-req">*</span></label>
                            <input type="date" class="form-control hm-control hm-date-input" id="add_holiday_date"
                                name="holiday_date" required>
                            <small class="hm-form-hint" data-day-hint-for="add_holiday_date"></small>
                            <span class="hm-field-error" data-error-for="holiday_date"></span>
                        </div>
                        <div class="mb-3">
                            <label for="add_holiday_type" class="hm-form-label">Holiday Type<span class="hm-req">*</span></label>
                            <select class="form-select hm-control" id="add_holiday_type" name="holiday_type" required>
                                <option value="">Select Holiday Type</option>
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="hm-field-error" data-error-for="holiday_type"></span>
                        </div>
                        <div class="mb-0">
                            <label for="add_description" class="hm-form-label">Description</label>
                            <textarea class="form-control hm-control" id="add_description" name="description" rows="3"
                                maxlength="500" placeholder="Shown when a user hovers the date on the calendar."></textarea>
                            <span class="hm-field-error" data-error-for="description"></span>
                        </div>
                        {{-- No Status field on Add: a new holiday is created Active.
                             It is changed afterwards from the row's switch, or from Edit. --}}
                        <input type="hidden" name="active_inactive" value="1">
                    </div>
                </div>
                <div class="modal-footer hm-modal-footer">
                    <button type="button" class="btn hm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn hm-btn-submit">Add Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Holiday Modal -->
<div class="modal fade" id="hmEditModal" tabindex="-1" aria-labelledby="hmEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content hm-modal border-0 shadow">
            <form id="hmEditForm" method="POST" action="" novalidate>
                @csrf
                <div class="modal-header hm-modal-header">
                    <h5 class="modal-title" id="hmEditModalLabel">Edit Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body hm-modal-body">
                    <div class="hm-field-card">
                        <div class="mb-3">
                            <label for="edit_holiday_name" class="hm-form-label">Holiday Name<span class="hm-req">*</span></label>
                            <input type="text" class="form-control hm-control" id="edit_holiday_name"
                                name="holiday_name" placeholder="e.g. Republic Day" maxlength="255" required>
                            <span class="hm-field-error" data-error-for="holiday_name"></span>
                        </div>
                        <div class="mb-3">
                            <label for="edit_holiday_date" class="hm-form-label">Holiday Date<span class="hm-req">*</span></label>
                            <input type="date" class="form-control hm-control hm-date-input" id="edit_holiday_date"
                                name="holiday_date" required>
                            <small class="hm-form-hint" data-day-hint-for="edit_holiday_date"></small>
                            <span class="hm-field-error" data-error-for="holiday_date"></span>
                        </div>
                        <div class="mb-3">
                            <label for="edit_holiday_type" class="hm-form-label">Holiday Type<span class="hm-req">*</span></label>
                            <select class="form-select hm-control" id="edit_holiday_type" name="holiday_type" required>
                                <option value="">Select Holiday Type</option>
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="hm-field-error" data-error-for="holiday_type"></span>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="hm-form-label">Description</label>
                            <textarea class="form-control hm-control" id="edit_description" name="description" rows="3"
                                maxlength="500" placeholder="Shown when a user hovers the date on the calendar."></textarea>
                            <span class="hm-field-error" data-error-for="description"></span>
                        </div>
                        <div class="mb-0">
                            <label for="edit_active_inactive" class="hm-form-label">Status<span class="hm-req">*</span></label>
                            <select class="form-select hm-control" id="edit_active_inactive" name="active_inactive" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <span class="hm-field-error" data-error-for="active_inactive"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer hm-modal-footer">
                    <button type="button" class="btn hm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn hm-btn-submit">Update Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="holidayColumnVisibilityModal" tabindex="-1"
    aria-labelledby="holidayColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="holidayColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="holidayColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
$(function () {
    const exportUrl = "{{ route('admin.holiday-master.export') }}";
    let currentStatus = 'all';

    const table = $('#holiday-master-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [],
        ajax: {
            url: "{{ route('admin.holiday-master.index') }}",
            data: function (d) {
                d.status_filter = currentStatus;
                d.year_filter = $('#yearFilter').val();
                d.type_filter = $('#typeFilter').val();
                d.from_date = $('#timePeriodFilter').data('from') || '';
                d.to_date = $('#timePeriodFilter').data('to') || '';
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'holiday_name', name: 'holiday_name' },
            { data: 'holiday_date_display', name: 'holiday_date' },
            { data: 'holiday_day', name: 'holiday_day', orderable: false, searchable: false },
            { data: 'holiday_type_display', name: 'holiday_type' },
            { data: 'year', name: 'year' },
            { data: 'description_display', name: 'description' },
            { data: 'status', name: 'active_inactive', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            emptyTable: 'No holidays found. Use "Add Holiday" to create one.',
        },
    });

    /* ── Time Period: date-range picker on holiday_date ── */
    const $period = $('#timePeriodFilter');

    $period.daterangepicker({
        autoUpdateInput: false,
        opens: 'left',
        locale: {
            format: 'DD-MM-YYYY',
            cancelLabel: 'Clear',
            applyLabel: 'Apply',
        },
        ranges: {
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Next 3 Months': [moment(), moment().add(3, 'months')],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Next Year': [moment().add(1, 'year').startOf('year'), moment().add(1, 'year').endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
        },
    });

    $period.on('apply.daterangepicker', function (ev, picker) {
        $(this)
            .val(picker.startDate.format('DD-MM-YYYY') + ' – ' + picker.endDate.format('DD-MM-YYYY'))
            .data('from', picker.startDate.format('YYYY-MM-DD'))
            .data('to', picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
    });

    $period.on('cancel.daterangepicker', function () {
        $(this).val('').removeData('from').removeData('to');
        table.ajax.reload();
    });

    /* ── Status pills ── */
    $('.programme-status-pill').on('click', function () {
        const $btn = $(this);
        $('.programme-status-pill')
            .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $btn.addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');
        currentStatus = $btn.data('hm-status');
        table.ajax.reload();
    });

    $('#yearFilter, #typeFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        $('#yearFilter').val('');
        $('#typeFilter').val('');
        $period.val('').removeData('from').removeData('to');
        table.search('');
        $('.programme-status-pill[data-hm-status="all"]').trigger('click');
    });

    /* ── Download: export current filters/search to CSV ── */
    $('#holidayDownload').on('click', function () {
        const params = $.param({
            status_filter: currentStatus,
            year_filter: $('#yearFilter').val() || '',
            type_filter: $('#typeFilter').val() || '',
            from_date: $period.data('from') || '',
            to_date: $period.data('to') || '',
            search: table.search() || '',
        });
        window.location.href = exportUrl + '?' + params;
    });

    /* ====================== Add / Edit modals (§3c) ====================== */

    const updateUrlTemplate = "{{ route('admin.holiday-master.update', ':id') }}";
    const $addModal = $('#hmAddModal');
    const $editModal = $('#hmEditModal');

    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /* Weekday hint under the date field — the grid shows a Day column, so the
       form previews the same thing before saving. */
    function refreshDayHint($input) {
        const $hint = $input.closest('.mb-3').find('[data-day-hint-for]');
        const value = $input.val();
        if (!value) {
            $hint.text('');
            return;
        }
        const parts = value.split('-');
        const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        $hint.text(isNaN(d.getTime()) ? '' : dayNames[d.getDay()]);
    }

    $(document).on('change', '.hm-date-input', function () {
        refreshDayHint($(this));
    });

    function clearErrors($form) {
        $form.find('.hm-field-error').removeClass('is-shown').text('');
        $form.find('.hm-control').removeClass('is-invalid');
    }

    /* Render the controller's own 422 payload against the matching fields, so the
       messages the user sees are exactly the server's rules. */
    function showErrors($form, errors) {
        clearErrors($form);
        $.each(errors || {}, function (field, messages) {
            const $slot = $form.find('[data-error-for="' + field + '"]');
            $slot.text([].concat(messages)[0] || '').addClass('is-shown');
            $form.find('[name="' + field + '"]').addClass('is-invalid');
        });
    }

    function submitModalForm($form, $modal) {
        const $submit = $form.find('button[type="submit"]');
        clearErrors($form);
        $submit.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                bootstrap.Modal.getInstance($modal[0])?.hide();
                toastr.success((res && res.message) || 'Saved successfully.');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showErrors($form, xhr.responseJSON && xhr.responseJSON.errors);
                    return;
                }
                toastr.error('Could not save the holiday. Please try again.');
            },
            complete: function () {
                $submit.prop('disabled', false);
            },
        });
    }

    $('#hmAddForm').on('submit', function (e) {
        e.preventDefault();
        submitModalForm($(this), $addModal);
    });

    $('#hmEditForm').on('submit', function (e) {
        e.preventDefault();
        submitModalForm($(this), $editModal);
    });

    /* Add: always open blank — a modal is reused across openings, so last
       session's values (and errors) would otherwise still be sitting there. */
    $addModal.on('hidden.bs.modal', function () {
        const $form = $('#hmAddForm');
        $form[0].reset();
        clearErrors($form);
        $form.find('[data-day-hint-for]').text('');
    });

    /* Edit: prefilled from the row's data-attributes — no extra round trip. */
    $(document).on('click', '.hm-edit-btn', function () {
        const $btn = $(this);
        const $form = $('#hmEditForm');

        clearErrors($form);
        $form.attr('action', updateUrlTemplate.replace(':id', encodeURIComponent($btn.data('id'))));
        $('#edit_holiday_name').val($btn.data('name'));
        $('#edit_holiday_date').val($btn.data('date'));
        $('#edit_holiday_type').val($btn.data('type'));
        $('#edit_description').val($btn.data('description'));
        $('#edit_active_inactive').val(String($btn.data('status')));
        refreshDayHint($('#edit_holiday_date'));

        bootstrap.Modal.getOrCreateInstance($editModal[0]).show();
    });

    /* ── Status toggle (dedicated, validated endpoint — not the generic
          .status-toggle handler in custom.js) ── */
    $(document).on('change', '.hm-status-toggle', function () {
        const $toggle = $(this);
        const id = $toggle.data('id');
        const active = $toggle.is(':checked') ? 1 : 0;
        const actionWord = active ? 'activate' : 'deactivate';

        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure? You want to ' + actionWord + ' this holiday?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, ' + actionWord,
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (!result.isConfirmed) {
                $toggle.prop('checked', !active);
                return;
            }

            $.ajax({
                url: "{{ route('admin.holiday-master.status', ':id') }}".replace(':id', encodeURIComponent(id)),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    active_inactive: active,
                },
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message);
                        // The badge and the switch live in different columns, and
                        // Delete is gated on status — redraw so both follow.
                        table.ajax.reload(null, false);
                    }
                },
                error: function () {
                    $toggle.prop('checked', !active);
                    toastr.error('Failed to update status.');
                }
            });
        });
    });

    /* ── Delete ── */
    $(document).on('click', '.hm-delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This will permanently delete this holiday.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ route('admin.holiday-master.destroy', ':id') }}".replace(':id', encodeURIComponent(id)),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function (res) {
                    toastr.success(res.message || 'Holiday deleted successfully.');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete holiday.';
                    toastr.error(message);
                },
            });
        });
    });

    /* ---------------- Column show / hide (DataTables API) ---------------- */
    const holidayColStorageKey = 'holidayGrid:hiddenColumns:v1';

    function holidayGetHiddenCols() {
        try {
            const raw = localStorage.getItem(holidayColStorageKey);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function holidayPersistHiddenCols(arr) {
        try { localStorage.setItem(holidayColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupHolidayColumns(dt) {
        if (!dt) {
            return;
        }
        const hidden = holidayGetHiddenCols();

        dt.columns().every(function () {
            const idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        const $grid = $('#holidayColumnToggleGrid');
        if (!$grid.length) {
            return;
        }
        $grid.empty();

        dt.columns().every(function () {
            const idx = this.index();
            const title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) {
                return;
            }

            const inputId = 'holidaycolvis_' + idx;
            const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                const h = holidayGetHiddenCols();
                const pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                holidayPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    table.on('init.dt', function () {
        setupHolidayColumns(table);
    });
    // Fallback in case the init event has already fired.
    setTimeout(function () {
        if ($('#holidayColumnToggleGrid').children().length === 0) {
            setupHolidayColumns(table);
        }
    }, 400);
});
</script>
@endpush
