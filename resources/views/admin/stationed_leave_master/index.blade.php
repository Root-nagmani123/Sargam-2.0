@extends('admin.layouts.master')

@section('title', 'Stationed Leave Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .stationed-leave-page .sl-filter-select {
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

    .stationed-leave-page .sl-filter-select:focus {
        border-color: #004a93;
        box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
    }

    .stationed-leave-page .sl-daterange-wrap {
        position: relative;
    }

    .stationed-leave-page .sl-daterange-input {
        width: 215px;
        padding-left: 2.25rem;
        padding-right: 0.875rem;
        cursor: pointer;
        background-image: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .stationed-leave-page .sl-daterange-input::placeholder {
        color: #344054;
    }

    .stationed-leave-page .sl-daterange-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #667085;
        font-size: 0.95rem;
        pointer-events: none;
    }

    .stationed-leave-page .sl-download-btn {
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

    .stationed-leave-page .sl-download-btn:hover {
        color: #004a93;
        background: #fff;
    }

    .stationed-leave-page .sl-download-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    @media (max-width: 767.98px) {
        .stationed-leave-page .sl-filter-select,
        .stationed-leave-page .sl-daterange-input {
            width: 100%;
        }
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid stationed-leave-page">
    <x-breadcrum
        title="Stationed Leave Master"
        buttonText="Configure Stationed Leave"
        :buttonUrl="route('admin.stationed-leave-master.create')"
        buttonIcon="add"
        buttonClass="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm" />

    <x-session_message />

    <div class="d-flex justify-content-end mb-3">
        <button type="button" id="stationedDownload" class="btn sl-download-btn">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
    </div>

    <section class="datatables" aria-labelledby="stationed-leave-heading">
        <div class="card border-0 shadow-sm overflow-hidden rounded-3">
            <div class="card-body p-3 p-md-4">

                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filters</span>
                        <select id="courseFilter" class="form-select sl-filter-select" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach ($courses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <div class="sl-daterange-wrap">
                            <i class="bi bi-calendar3 sl-daterange-icon" aria-hidden="true"></i>
                            <input type="text" id="timePeriodFilter"
                                class="form-control sl-filter-select sl-daterange-input"
                                placeholder="Time Period" autocomplete="off" readonly
                                aria-label="Filter by effective-from date range">
                        </div>
                        <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                            Reset Filters
                        </button>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" id="btnStationedColumns"
                            data-bs-toggle="modal" data-bs-target="#stationedColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div id="stationedDtSearch" class="programme-dt-search"
                            data-dt-search-for="stationed-leave-table"></div>
                    </div>
                </div>

                <p class="small text-secondary d-lg-none mb-2" role="note">
                    Scroll inside the table area to see all rows and columns.
                </p>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap w-100 programme-dt-table"
                            id="stationed-leave-table">
                            <thead>
                                <tr>
                                    <th>S. No.</th>
                                    <th>Course</th>
                                    <th>Effective From</th>
                                    <th>PT Timing</th>
                                    <th>Approval Required</th>
                                    <th>Faculty Count</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div id="stationedDtFooter"
                        class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="stationed-leave-table"></div>
                </div>

            </div>
        </div>
    </section>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="stationedColumnVisibilityModal" tabindex="-1"
    aria-labelledby="stationedColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="stationedColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="stationedColumnToggleGrid"></div>
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
    const exportUrl = "{{ route('admin.stationed-leave-master.export') }}";

    const table = $('#stationed-leave-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.stationed-leave-master.index') }}",
            data: function (d) {
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
            { data: 'approval_required_display', name: 'is_faculty_approval_required' },
            { data: 'faculty_count_display', name: 'approvers_count' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: { emptyTable: 'No stationed leave configuration found.' },
    });

    /* ── Time Period date-range on effective_from ── */
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

    /* ── Course filter ── */
    $('#courseFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        $('#courseFilter').val('');
        $period.val('').removeData('from').removeData('to');
        table.search('');
        table.ajax.reload();
    });

    /* ── Download ── */
    $('#stationedDownload').on('click', function () {
        const params = $.param({
            course_filter: $('#courseFilter').val() || '',
            from_date: $period.data('from') || '',
            to_date: $period.data('to') || '',
        });
        window.location.href = exportUrl + '?' + params;
    });

    /* ── Status toggle ── */
    $(document).on('change', '.stationed-leave-status-toggle', function () {
        const id = $(this).data('id');
        const active = $(this).is(':checked') ? 1 : 0;
        const $toggle = $(this);

        $.ajax({
            url: "{{ route('admin.stationed-leave-master.status', ':id') }}".replace(':id', id),
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', active_inactive: active },
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
    $(document).on('click', '.stationed-leave-delete-btn', function () {
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
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('admin.stationed-leave-master.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    toastr.success(res.message || 'Record deleted successfully.');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete record.');
                },
            });
        });
    });

    /* ---------------- Column show / hide ---------------- */
    const stationedColStorageKey = 'stationedGrid:hiddenColumns:v1';

    function stationedGetHiddenCols() {
        try {
            const raw = localStorage.getItem(stationedColStorageKey);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function stationedPersistHiddenCols(arr) {
        try { localStorage.setItem(stationedColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupStationedColumns(dt) {
        if (!dt) {
            return;
        }
        const hidden = stationedGetHiddenCols();

        dt.columns().every(function () {
            const idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        const $grid = $('#stationedColumnToggleGrid');
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

            const inputId = 'stationedcolvis_' + idx;
            const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                const h = stationedGetHiddenCols();
                const pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                stationedPersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    table.on('init.dt', function () {
        setupStationedColumns(table);
    });
    setTimeout(function () {
        if ($('#stationedColumnToggleGrid').children().length === 0) {
            setupStationedColumns(table);
        }
    }, 400);
});
</script>
@endpush
