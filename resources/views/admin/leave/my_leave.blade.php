@extends('admin.layouts.master')

@section('title', 'My Leave Applications')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@section('content')

@include('admin.leave.partials.styles')

<div class="container-fluid leave-module">
    <x-breadcrum
        title="My Leave Applications"
        buttonText="Apply Leave"
        :buttonUrl="route('leave.apply')"
        buttonIcon="add"
        buttonClass="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm" />

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group"
            aria-label="Filter by status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                    data-status="" aria-pressed="true" aria-current="true">All</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    data-status="1" aria-pressed="false">Pending</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    data-status="2" aria-pressed="false">Approved</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                    data-status="3" aria-pressed="false">Rejected</button>
            </li>
        </ul>
        <button type="button" id="myLeaveDownload" class="btn fl-download-btn">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <select id="filter_leave_type" class="form-select fl-filter-select" aria-label="Filter by leave type">
                        <option value="">Leave Type</option>
                        <option value="PT_EXEMPTION">PT Exemption</option>
                        <option value="STATIONED_LEAVE">Stationed Leave</option>
                    </select>
                    <div class="fl-daterange-wrap">
                        <i class="bi bi-calendar3 fl-daterange-icon" aria-hidden="true"></i>
                        <input type="text" id="timePeriodFilter"
                            class="form-control fl-filter-select fl-daterange-input"
                            placeholder="Time Period" autocomplete="off" readonly
                            aria-label="Filter by leave start-date range">
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">
                        Reset Filters
                    </button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnLeaveColumns"
                        data-bs-toggle="modal" data-bs-target="#myLeaveColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="myLeaveDtSearch" class="programme-dt-search"
                        data-dt-search-for="my-leave-table"></div>
                </div>
            </div>

            <p class="small text-secondary d-lg-none mb-2" role="note">
                Scroll inside the table area to see all rows and columns.
            </p>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap w-100 programme-dt-table"
                        id="my-leave-table">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Leave Type</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Total Days</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="myLeaveDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="my-leave-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="myLeaveColumnVisibilityModal" tabindex="-1"
    aria-labelledby="myLeaveColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="myLeaveColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="myLeaveColumnToggleGrid"></div>
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
    const exportUrl = "{{ route('leave.my-leave.export') }}";
    let currentStatus = '';

    const table = $('#my-leave-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('leave.my-leave') }}",
            data: function (d) {
                d.leave_type = $('#filter_leave_type').val();
                d.status = currentStatus;
                d.from_date = $('#timePeriodFilter').data('from') || '';
                d.to_date = $('#timePeriodFilter').data('to') || '';
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'leave_type_label', name: 'leave_type' },
            { data: 'from_date_display', name: 'from_date' },
            { data: 'to_date_display', name: 'to_date' },
            { data: 'total_days_display', name: 'total_days' },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        language: {
            emptyTable: 'No leave applications found.',
        },
    });

    /* ── Status tabs ── */
    $('.programme-status-tabs .programme-status-pill').on('click', function () {
        $('.programme-status-tabs .programme-status-pill')
            .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $(this).addClass('active').attr('aria-pressed', 'true').attr('aria-current', 'true');
        currentStatus = String($(this).data('status'));
        table.ajax.reload();
    });

    /* ── Time Period date-range ── */
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

    /* ── Leave type filter ── */
    $('#filter_leave_type').on('change', function () {
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        // Reset the filters (leave type, time period, search) but stay on the
        // currently selected status tab — don't force it back to "All".
        $('#filter_leave_type').val('');
        $period.val('').removeData('from').removeData('to');
        table.search('');
        table.ajax.reload();
    });

    /* ── Download ── */
    $('#myLeaveDownload').on('click', function () {
        const params = $.param({
            leave_type: $('#filter_leave_type').val() || '',
            status: currentStatus,
            from_date: $period.data('from') || '',
            to_date: $period.data('to') || '',
        });
        window.location.href = exportUrl + '?' + params;
    });

    /* ── Delete ── */
    $(document).on('click', '.leave-delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This leave application will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ route('leave.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Delete failed.');
                }
            });
        });
    });

    /* ---------------- Column show / hide ---------------- */
    const leaveColStorageKey = 'myLeaveGrid:hiddenColumns:v1';

    function leaveGetHiddenCols() {
        try {
            const raw = localStorage.getItem(leaveColStorageKey);
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function leavePersistHiddenCols(arr) {
        try { localStorage.setItem(leaveColStorageKey, JSON.stringify(arr)); } catch (e) {}
    }

    function setupLeaveColumns(dt) {
        if (!dt) {
            return;
        }
        const hidden = leaveGetHiddenCols();

        dt.columns().every(function () {
            const idx = this.index();
            this.visible(hidden.indexOf(idx) === -1, false);
        });
        dt.columns.adjust();

        const $grid = $('#myLeaveColumnToggleGrid');
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

            const inputId = 'myleavecolvis_' + idx;
            const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(idx) === -1);

            $cb.on('change', function () {
                const h = leaveGetHiddenCols();
                const pos = h.indexOf(idx);
                if (this.checked) {
                    if (pos !== -1) h.splice(pos, 1);
                } else {
                    if (pos === -1) h.push(idx);
                }
                leavePersistHiddenCols(h);
                dt.column(idx).visible(this.checked, false);
                dt.columns.adjust();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    table.on('init.dt', function () {
        setupLeaveColumns(table);
    });
    setTimeout(function () {
        if ($('#myLeaveColumnToggleGrid').children().length === 0) {
            setupLeaveColumns(table);
        }
    }, 400);
});
</script>
@endpush
