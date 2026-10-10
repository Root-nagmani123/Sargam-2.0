@extends('admin.layouts.master')

@section('title', 'Leave Applied on Behalf of OT')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@section('setup_content')

@include('admin.leave.partials.styles')

<div class="container-fluid leave-module leave-on-behalf-list">
    <x-breadcrum title="Leave Applied on Behalf of OT" :showBack="false">
        <a href="{{ route('admin.leave-on-behalf.create') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-2 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">add</i>
            <span>Apply Leave</span>
        </a>
    </x-breadcrum>
    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="dropdown">
            <button type="button" id="lobDownload" class="btn fl-download-btn dropdown-toggle"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="lobDownload">
                <li><button type="button" class="dropdown-item lob-export-option" data-format="excel">
                    <i class="bi bi-file-earmark-excel me-2" aria-hidden="true"></i>Excel (.xlsx)
                </button></li>
                <li><button type="button" class="dropdown-item lob-export-option" data-format="pdf">
                    <i class="bi bi-file-earmark-pdf me-2" aria-hidden="true"></i>PDF
                </button></li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>
                    <select id="courseFilter" class="form-select fl-filter-select" aria-label="Filter by course">
                        <option value="">Course Name</option>
                        @foreach ($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="fl-daterange-wrap">
                        <i class="bi bi-calendar3 fl-daterange-icon" aria-hidden="true"></i>
                        <input type="text" id="timePeriodFilter"
                            class="form-control fl-filter-select fl-daterange-input"
                            placeholder="Time Period" autocomplete="off" readonly
                            aria-label="Filter by leave start-date range">
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div id="lobDtSearch" class="programme-dt-search" data-dt-search-for="leave-on-behalf-table"></div>
                </div>
            </div>

            <p class="small text-secondary d-lg-none mb-2" role="note">
                Scroll inside the table area to see all rows and columns.
            </p>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap w-100 programme-dt-table"
                        id="leave-on-behalf-table">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Course Name</th>
                                <th>OT Code</th>
                                <th>OT Name</th>
                                <th>Nature of Leave</th>
                                <th>Date From</th>
                                <th>Date To</th>
                                <th>Time From</th>
                                <th>Time To</th>
                                <th>Total Days</th>
                                <th>Reason</th>
                                <th>Recorded By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div id="lobDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="leave-on-behalf-table"></div>
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
    const exportUrl = "{{ route('admin.leave-on-behalf.export') }}";
    const $period = $('#timePeriodFilter');

    const table = $('#leave-on-behalf-table').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('admin.leave-on-behalf.index') }}",
            data: function (d) {
                d.course_filter = $('#courseFilter').val();
                d.from_date = $period.data('from') || '';
                d.to_date = $period.data('to') || '';
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'course_name', name: 'course.course_name', orderable: false },
            { data: 'ot_code', name: 'student.generated_OT_code', orderable: false },
            { data: 'ot_name', name: 'student.display_name', orderable: false },
            { data: 'nature_name', name: 'nature.nature_name', orderable: false },
            { data: 'from_date_display', name: 'from_date' },
            { data: 'to_date_display', name: 'to_date' },
            { data: 'time_from_display', name: 'time_from' },
            { data: 'time_to_display', name: 'time_to' },
            { data: 'total_days_display', name: 'total_days' },
            { data: 'reason_text', name: 'reason', orderable: false },
            { data: 'recorded_by', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
        ],
        language: {
            emptyTable: 'No leave has been recorded on behalf of an officer trainee yet.',
        },
    });

    /* ── Time Period: date-range picker on the leave start date ── */
    $period.daterangepicker({
        autoUpdateInput: false,
        opens: 'left',
        locale: { format: 'DD-MM-YYYY', cancelLabel: 'Clear', applyLabel: 'Apply' },
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
        $(this).val('').data('from', '').data('to', '');
        table.ajax.reload();
    });

    $('#courseFilter').on('change', function () { table.ajax.reload(); });

    $('#resetFilters').on('click', function () {
        $('#courseFilter').val('');
        $period.val('').data('from', '').data('to', '');
        table.search('');
        table.ajax.reload();
    });

    /* ── Download: same filters as the grid, so list and file agree ── */
    $(document).on('click', '.lob-export-option', function () {
        const params = $.param({
            course_filter: $('#courseFilter').val() || '',
            from_date: $period.data('from') || '',
            to_date: $period.data('to') || '',
            format: $(this).data('format'),
        });
        window.location.href = exportUrl + '?' + params;
    });
});
</script>
@endpush
