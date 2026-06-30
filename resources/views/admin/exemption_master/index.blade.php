@extends('admin.layouts.master')

@section('title', 'PT Exemption Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .pt-exemption-page .pt-filter-select {
        width: 180px;
        min-height: 40px;
        height: 40px;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        font-size: 0.9375rem;
        color: #344054;
        padding: 0.5rem 2.25rem 0.5rem 0.875rem;
        background-position: right 0.75rem center;
    }

    .pt-exemption-page .pt-filter-select:focus {
        border-color: #004a93;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    }

    /* Time-period date-range input */
    .pt-exemption-page .pt-daterange-wrap {
        position: relative;
    }

    .pt-exemption-page .pt-daterange-input {
        width: 215px;
        padding-left: 2.25rem;
        padding-right: 0.875rem;
        cursor: pointer;
        background-image: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pt-exemption-page .pt-daterange-input::placeholder {
        color: #344054;
    }

    .pt-exemption-page .pt-daterange-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #667085;
        font-size: 0.95rem;
        pointer-events: none;
    }

    .pt-exemption-page .pt-download-btn {
        height: 40px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0 1.1rem;
        font-size: 0.9375rem;
        font-weight: 500;
        color: #004a93;
        border-radius: 8px;
        background: #fff;
    }

    .pt-exemption-page .pt-download-btn:hover {
        background: #fff;

        color: #004a93;
    }

    .pt-exemption-page .pt-download-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    @media (max-width: 767.98px) {
        .pt-exemption-page .pt-filter-select,
        .pt-exemption-page .pt-daterange-input {
            width: 100%;
        }
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid pt-exemption-page">
    <x-breadcrum
        title="PT Exemption Master"
        buttonText="Configure PT Exemption"
        :buttonUrl="route('admin.pt-exemption-master.create')"
        buttonIcon="add"
        buttonClass="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm" />

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group"
            aria-label="Filter by status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    id="filterActive" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    id="filterArchive" aria-pressed="false">Archive</button>
            </li>
        </ul>
        <button type="button" id="exemptionDownload" class="btn pt-download-btn ">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
    </div>

    <section class="datatables" aria-labelledby="pt-exemption-heading">
        <div class="card border-0 shadow-sm overflow-hidden rounded-3">
            <div class="card-body p-3 p-md-4">

                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>
                        <select id="courseFilter" class="form-select pt-filter-select" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach ($coursesActive ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <div class="pt-daterange-wrap">
                            <i class="bi bi-calendar3 pt-daterange-icon" aria-hidden="true"></i>
                            <input type="text" id="timePeriodFilter"
                                class="form-control pt-filter-select pt-daterange-input"
                                placeholder="Time Period" autocomplete="off" readonly
                                aria-label="Filter by effective-from date range">
                        </div>
                        <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                            Reset Filters
                        </button>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" id="btnExemptionColumns"
                            data-bs-toggle="modal" data-bs-target="#exemptionColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div id="exemptionDtSearch" class="programme-dt-search"
                            data-dt-search-for="exemption-master-table"></div>
                    </div>
                </div>

                <p class="small text-secondary d-lg-none mb-2" role="note">
                    Scroll inside the table area to see all rows and columns.
                </p>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap w-100 programme-dt-table"
                            id="exemption-master-table">
                            <thead>
                                <tr>
                                    <th>S. No.</th>
                                    <th>Course</th>
                                    <th>Effective From</th>
                                    <th>PT Timing</th>
                                    <th>Gender</th>
                                    <th>PT Exemption Count (Days)</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div id="exemptionDtFooter"
                        class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="exemption-master-table"></div>
                </div>

            </div>
        </div>
    </section>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="exemptionColumnVisibilityModal" tabindex="-1"
    aria-labelledby="exemptionColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="exemptionColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="exemptionColumnToggleGrid"></div>
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
    const exportUrl = "{{ route('admin.pt-exemption-master.export') }}";
    const coursesByStatus = {
        active: @json($coursesActive ?? []),
        archive: @json($coursesArchive ?? []),
    };
    let currentStatus = 'active';

    const table = $('#exemption-master-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.pt-exemption-master.index') }}",
            data: function (d) {
                d.pk = $('#pk').val();
                d.active_inactive = $('#active_inactive').val();
                d.status_filter = currentStatus;
                d.course_filter = $('#courseFilter').val();
                d.from_date = $('#timePeriodFilter').data('from') || '';
                d.to_date = $('#timePeriodFilter').data('to') || '';
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'course_name', name: 'course.course_name' },
            { data: 'effective_from_display', name: 'effective_from' },
            { data: 'apply_cutoff_time_display', name: 'apply_cutoff_time', orderable: false, searchable: false },
            { data: 'gender', name: 'gender' },
            { data: 'exemption_days_display', name: 'exemption_days' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: {
            emptyTable: 'No PT exemption configuration found.',
        },
    });

    /* ── Time Period: date-range picker on effective_from ── */
    const $period = $('#timePeriodFilter');

    $period.daterangepicker({
        autoUpdateInput: false,
        opens: 'right',
        locale: {
            format: 'DD-MM-YYYY',
            cancelLabel: 'Clear',
            applyLabel: 'Apply',
        },
        ranges: {
            'Today': [moment(), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last 3 Months': [moment().subtract(3, 'months').startOf('day'), moment()],
            'Last 6 Months': [moment().subtract(6, 'months').startOf('day'), moment()],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
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

    /* ── Active / Archive status tabs ── */
    function populateCourseFilter(status) {
        const $sel = $('#courseFilter');
        const data = coursesByStatus[status] || {};
        $sel.find('option:not(:first)').remove();
        $.each(data, function (pk, name) {
            $sel.append($('<option>', { value: pk, text: name }));
        });
        $sel.val('');
    }

    function setStatusTab($btn, status) {
        $('#filterActive, #filterArchive')
            .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $btn.addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');
        currentStatus = status;
        populateCourseFilter(status);
        table.ajax.reload();
    }

    $('#filterActive').on('click', function () {
        setStatusTab($(this), 'active');
    });

    $('#filterArchive').on('click', function () {
        setStatusTab($(this), 'archive');
    });

    /* ── Course filter → reload table ── */
    $('#courseFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        $('#courseFilter').val('');
        $period.val('').removeData('from').removeData('to');
        table.search('');
        setStatusTab($('#filterActive'), 'active');
    });

    /* ── Download: export current filters/search to CSV ── */
    $('#exemptionDownload').on('click', function () {
        const params = $.param({
            status_filter: currentStatus,
            course_filter: $('#courseFilter').val() || '',
            from_date: $period.data('from') || '',
            to_date: $period.data('to') || '',
            search: table.search() || '',
        });
        window.location.href = exportUrl + '?' + params;
    });

    /* ── Status toggle ── */
    $(document).on('change', '.exemption-status-toggle', function () {
        const id = $(this).data('id');
        const active = $(this).is(':checked') ? 1 : 0;
        const $toggle = $(this);

        $.ajax({
            url: "{{ route('admin.pt-exemption-master.status', ':id') }}".replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                active_inactive: active,
            },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                }
            },
            error: function () {
                $toggle.prop('checked', !active);
                toastr.error('Failed to update status.');
            }
        });
    });

    /* ── Delete ── */
    $(document).on('click', '.exemption-delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This record will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ route('admin.pt-exemption-master.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function (res) {
                    toastr.success(res.message || 'Record deleted successfully.');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete record.';
                    toastr.error(message);
                },
            });
        });
    });

    /* ---------------- Column show / hide (DataTables API) ---------------- */
    const exemptionColStorageKey = 'exemptionGrid:hiddenColumns:v1';

    function exemptionGetHiddenCols() {
        try {
            const raw = localStorage.getItem(exemptionColStorageKey);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function exemptionPersistHiddenCols(arr) {
        try { localStorage.setItem(exemptionColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupExemptionColumns(dt) {
        if (!dt) {
            return;
        }
        const hidden = exemptionGetHiddenCols();

        dt.columns().every(function () {
            const idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        const $grid = $('#exemptionColumnToggleGrid');
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

            const inputId = 'exemptioncolvis_' + idx;
            const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                const h = exemptionGetHiddenCols();
                const pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                exemptionPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    table.on('init.dt', function () {
        setupExemptionColumns(table);
    });
    // Fallback in case the init event has already fired.
    setTimeout(function () {
        if ($('#exemptionColumnToggleGrid').children().length === 0) {
            setupExemptionColumns(table);
        }
    }, 400);
});
</script>
@endpush

<input type="hidden" id="pk" value="">
<input type="hidden" id="active_inactive" value="">
