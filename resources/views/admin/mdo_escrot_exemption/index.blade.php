@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('setup_content')
<style>
/* Filter toolbar (matches updated design) */
.mee-filters-label { font-weight: 600; font-size: 0.9rem; color: #1f2937; margin-right: 4px; }
.mee-filter-control {
    height: 44px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 14px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1f2937;
    background: #fff;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    line-height: 1;
}
.mee-filter-control:hover { border-color: #b6c0cc; }
.mee-filter-control:focus { outline: none; border-color: #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.18); }
select.mee-filter-control {
    display: inline-block;
    min-width: 160px;
    max-width: 220px;
    padding-right: 34px;
    text-overflow: ellipsis;
}
.mee-icon-btn { width: 44px; padding: 0; justify-content: center; }

/* Time Period chip */
.mee-time-period-filter { display: inline-flex; }
.mee-tp-input {
    min-width: 170px;
    padding-left: 38px;
    padding-right: 32px;
    background: #fff;
    cursor: pointer;
}
.mee-tp-input::placeholder { color: #1f2937; opacity: 1; }
.mee-tp-ico { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #667085; font-size: 16px; pointer-events: none; }
.mee-tp-caret { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #667085; font-size: 12px; pointer-events: none; }

/* +3 Filters link */
.mee-more-filters { color: var(--bs-primary); font-weight: 600; text-decoration: underline; white-space: nowrap; }
.mee-more-filters.mee-more-filters-active { font-weight: 700; }
.mee-extra-menu { min-width: 240px; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 8px 24px rgba(16,24,40,0.12); }

/* Reset Filters = red outline */
.mee-reset { color: var(--bs-danger); border-color: var(--bs-danger); font-weight: 600; }
.mee-reset:hover { background: var(--bs-danger); color: #fff; border-color: var(--bs-danger); }

/* Search dropdown */
.mee-search-menu { min-width: 260px; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 8px 24px rgba(16,24,40,0.12); }

/* Column Visibility modal — grid of bordered checkbox chips */
.mee-col-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.mee-col-chip {
    display: flex; align-items: center; gap: 8px; margin: 0;
    padding: 0.65rem 0.85rem; border: 1px solid #e2e8f0; border-radius: 8px;
    background: #fff; cursor: pointer; font-size: 0.9rem; font-weight: 500; color: #1f2937; user-select: none;
}
.mee-col-chip:hover { border-color: #b6c0cc; background: #f8fafc; }
.mee-col-chip.is-checked { border-color: var(--bs-primary); box-shadow: inset 0 0 0 1px var(--bs-primary); }
@media (max-width: 767.98px) { .mee-col-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 479.98px) { .mee-col-grid { grid-template-columns: 1fr; } }

/* Bottom bar: pagination (left) + "Showing [n] of N items" (right) */
.mee-master-page .mee-dt-bottom { margin-top: 1rem; }
.mee-master-page .mee-dt-count,
.mee-master-page .mee-dt-count .dataTables_info,
.mee-master-page .mee-dt-count .dataTables_length {
    color: #667085;
    font-size: 0.875rem;
}
.mee-master-page .mee-dt-count .dataTables_length,
.mee-master-page .mee-dt-count .dataTables_info { margin: 0; padding: 0; }
.mee-master-page .mee-dt-count .dataTables_length label {
    margin: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.mee-master-page .mee-dt-count .dataTables_length select.form-select,
.mee-master-page .mee-dt-count .dataTables_length select {
    width: auto;
    min-width: 76px;
    display: inline-block;
    border-radius: 6px;
    margin: 0 0.25rem;
}
</style>
<div class="container-fluid mee-master-page">
    <x-breadcrum title="Escort/ Moderator Duty">
        <div class="d-inline-flex flex-wrap align-items-center gap-2">
            <button type="button"
                id="meeBulkUploadBtn"
                class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm text-nowrap">
                <i class="bi bi-upload" aria-hidden="true"></i>
                <span>Bulk Upload</span>
            </button>
            <button type="button"
                id="meeAddExemptionBtn"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm text-nowrap">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add New MDO/ Escort Exemption</span>
            </button>
        </div>
    </x-breadcrum>

    <x-session_message />

    @php
        $activeParams = ['filter' => 'active'];
        $archiveParams = ['filter' => 'archive'];
        foreach (['course_filter', 'year_filter', 'duty_type_filter', 'time_from_filter', 'time_to_filter', 'from_date_filter', 'to_date_filter'] as $param) {
            if (request($param)) {
                $activeParams[$param] = request($param);
                $archiveParams[$param] = request($param);
            }
        }
        $timePeriodLabel = '';
        if (request('from_date_filter') && request('to_date_filter')) {
            try {
                $timePeriodLabel = \Carbon\Carbon::parse(request('from_date_filter'))->format('d/m/Y')
                    . ' - '
                    . \Carbon\Carbon::parse(request('to_date_filter'))->format('d/m/Y');
            } catch (\Exception $e) {
                $timePeriodLabel = '';
            }
        }
    @endphp

    <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white shadow-sm mb-0" role="group"
            aria-label="Course Status Filter">
            <li class="nav-item" role="presentation">
                <a href="{{ route('mdo-escrot-exemption.index', $activeParams) }}"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ ($filter ?? 'active') === 'active' ? 'active' : '' }}"
                    id="filterActive"
                    aria-pressed="{{ ($filter ?? 'active') === 'active' ? 'true' : 'false' }}"
                    {{ ($filter ?? 'active') === 'active' ? 'aria-current=true' : '' }}>
                    Active
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ route('mdo-escrot-exemption.index', $archiveParams) }}"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ ($filter ?? 'active') === 'archive' ? 'active' : '' }}"
                    id="filterArchive"
                    aria-pressed="{{ ($filter ?? 'active') === 'archive' ? 'true' : 'false' }}"
                    {{ ($filter ?? 'active') === 'archive' ? 'aria-current=true' : '' }}>
                    Archived
                </a>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
            <button type="button" id="printDownloadBtn"
                class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm" style="border:0;background:#fff;color:#004a93;">
                <i class="material-icons material-symbols-rounded" aria-hidden="true">print</i>
                <span>Print</span>
            </button>
            <button type="button" id="downloadBtn"
                class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 rounded-1 fw-semibold shadow-sm" style="border:0;background:#fff;color:#004a93;">
                <i class="material-icons material-symbols-rounded" aria-hidden="true">download</i>
                <span>Download</span>
            </button>
        </div>
    </div>

    <div class="datatables">
        <div class="card mee-dt-card border-0 shadow-sm rounded-1 overflow-hidden">
            <div class="card-body p-3 p-md-4">

                {{-- Filter toolbar (matches updated design) --}}
                <div class="mee-toolbar d-flex flex-wrap align-items-center gap-2 mb-4">
                    <span class="mee-filters-label">Filters</span>

                    {{-- Course Name --}}
                    <select id="course_filter" class="form-select mee-filter-control" aria-label="Filter by course name">
                        <option value="">Course Name</option>
                        @foreach ($courseMaster as $id => $name)
                        <option value="{{ $id }}" {{ (string) request('course_filter') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>

                    {{-- Duty Type --}}
                    <select id="duty_type_filter" class="form-select mee-filter-control" aria-label="Filter by duty type">
                        <option value="">Duty Type</option>
                        @foreach ($dutyTypes as $id => $name)
                        <option value="{{ $id }}" {{ (string) request('duty_type_filter') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>

                    {{-- Time Period (flatpickr range) --}}
                    <div class="mee-time-period-filter position-relative">
                        <input type="hidden" id="from_date_filter" value="{{ request('from_date_filter') }}">
                        <input type="hidden" id="to_date_filter" value="{{ request('to_date_filter') }}">
                        <i class="bi bi-calendar3 mee-tp-ico" aria-hidden="true"></i>
                        <input type="text" id="mee_time_period_picker"
                            class="mee-filter-control mee-tp-input"
                            placeholder="Time Period" value="{{ $timePeriodLabel }}"
                            readonly autocomplete="off" aria-label="Filter by time period">
                        <i class="bi bi-chevron-down mee-tp-caret" aria-hidden="true"></i>
                    </div>

                    {{-- +3 Filters popover (Year, Time From, Time To) --}}
                    <div class="dropdown">
                        <button type="button" class="btn btn-link p-0 mee-more-filters" id="meeExtraFiltersToggle"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            +3 Filters
                        </button>
                        <div class="dropdown-menu p-3 mee-extra-menu" aria-labelledby="meeExtraFiltersToggle">
                            <h6 class="fw-semibold mb-0">Filters</h6>
                            <hr class="my-3 opacity-50">
                            <div class="d-flex flex-column gap-3">
                                <div>
                                    <label for="year_filter" class="form-label small fw-medium mb-1">Year</label>
                                    <select id="year_filter" class="form-select form-select-sm" aria-label="Filter by year">
                                        <option value="">Year</option>
                                        @foreach ($years as $year => $yearValue)
                                        <option value="{{ $year }}" {{ (string) request('year_filter') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="time_from_filter" class="form-label small fw-medium mb-1">Time From</label>
                                    <input type="time" id="time_from_filter" class="form-control form-control-sm"
                                        value="{{ request('time_from_filter') }}" aria-label="Filter by time from">
                                </div>
                                <div>
                                    <label for="time_to_filter" class="form-label small fw-medium mb-1">Time To</label>
                                    <input type="time" id="time_to_filter" class="form-control form-control-sm"
                                        value="{{ request('time_to_filter') }}" aria-label="Filter by time to">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Reset --}}
                    <button type="button" class="mee-filter-control mee-reset" id="resetFilters">Reset Filters</button>

                    {{-- Right cluster: Columns + Search --}}
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="mee-filter-control" id="meeColumnsToggle"
                            data-bs-toggle="modal" data-bs-target="#meeColumnsModal">
                            <span class="d-none d-md-inline">Columns</span>
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">view_column</i>
                        </button>

                        <div class="dropdown">
                            <button type="button" class="mee-filter-control mee-icon-btn" id="meeSearchToggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Search">
                                <i class="material-icons material-symbols-rounded" aria-hidden="true">search</i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2 mee-search-menu">
                                <input type="text" id="meeTableSearch" class="form-control"
                                    placeholder="Search records..." autocomplete="off" aria-label="Search records">
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="filter_status" value="{{ $filter ?? 'active' }}">

                <div class="programme-dt-panel mee-dt-panel">
                    <div class="table-responsive mee-dt-scroll">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.mdo_escrot_exemption.partials.add_modal')
@include('admin.mdo_escrot_exemption.partials.edit_modal')
@include('admin.mdo_escrot_exemption.partials.student_list_modal')
@include('admin.mdo_escrot_exemption.partials.bulk_upload_modal')

<!-- Column Visibility modal -->
<div class="modal fade" id="meeColumnsModal" tabindex="-1" aria-labelledby="meeColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="meeColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mee-col-grid" id="meeColumnsGrid"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation -->
<div class="modal fade programme-confirm-modal-root" id="meeDeleteConfirmModal" tabindex="-1"
    aria-labelledby="meeDeleteConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered programme-confirm-dialog">
        <div class="modal-content programme-confirm-modal border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-body text-center px-4 px-md-5 py-5">
                <div class="programme-confirm-icon programme-confirm-icon--danger mb-4" role="img" aria-hidden="true">
                    <i class="bi bi-exclamation-lg"></i>
                </div>
                <h2 class="h4 fw-bold text-dark mb-3" id="meeDeleteConfirmTitle">Delete This Record?</h2>
                <p class="programme-confirm-message programme-confirm-message--danger mb-4 mb-md-5">
                    Are you sure you want to delete this Escort Exemption?
                </p>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-stretch programme-confirm-actions">
                    <button type="button"
                        class="btn btn-lg rounded-1 programme-confirm-btn programme-confirm-cancel--danger"
                        id="meeDeleteConfirmCancel"
                        data-bs-dismiss="modal">
                        <span class="programme-confirm-btn-line">Cancel, Keep it</span>
                    </button>
                    <button type="button"
                        class="btn btn-lg rounded-1 programme-confirm-btn programme-confirm-ok--danger"
                        id="meeDeleteConfirmOk">
                        <span class="programme-confirm-btn-line">Yes, Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
{!! $dataTable->scripts() !!}
<script>
$(document).ready(function() {
    var table = $('#mdoescot-table').DataTable();
    var meeTimePeriodPicker = null;
    var pendingDeleteForm = null;
    var meeDeleteModalEl = document.getElementById('meeDeleteConfirmModal');
    var meeDeleteModal = meeDeleteModalEl ? bootstrap.Modal.getOrCreateInstance(meeDeleteModalEl) : null;

    initMeeAddModal(table);
    initMeeEditModal(table);
    initMeeBulkUploadModal(table);

    function meeBindDeleteActions() {
        $('#mdoescot-table form[id^="delete-form-"] button[type="submit"]').removeAttr('onclick');
    }

    table.on('draw.dt', function() {
        var info = table.page.info();
        $('#total-records-count').text(info.recordsFiltered || info.recordsTotal || 0);
        meeBindDeleteActions();
    });

    meeBindDeleteActions();

    // 🔍 Search (icon dropdown) → server-side global search
    var meeSearchTimer;
    $('#meeTableSearch').on('keyup', function() {
        var value = this.value;
        clearTimeout(meeSearchTimer);
        meeSearchTimer = setTimeout(function() {
            table.search(value).draw();
        }, 400);
    });
    $('#meeSearchToggle').on('shown.bs.dropdown', function() {
        setTimeout(function() { $('#meeTableSearch').trigger('focus'); }, 50);
    });

    // 📅 Time Period range picker
    if (typeof flatpickr !== 'undefined') {
        var fpDefaults = [];
        @if(request('from_date_filter') && request('to_date_filter'))
        fpDefaults = ['{{ request('from_date_filter') }}', '{{ request('to_date_filter') }}'];
        @endif

        meeTimePeriodPicker = flatpickr('#mee_time_period_picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            showMonths: 2,
            defaultDate: fpDefaults.length ? fpDefaults : null,
            locale: { rangeSeparator: ' - ' },
            onReady: function (_d, _s, instance) { instance.calendarContainer.classList.add('mee-flatpickr-theme'); },
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    $('#from_date_filter').val(meeTimePeriodPicker.formatDate(selectedDates[0], 'Y-m-d'));
                    $('#to_date_filter').val(meeTimePeriodPicker.formatDate(selectedDates[1], 'Y-m-d'));
                    table.ajax.reload();
                } else if (selectedDates.length === 0) {
                    $('#from_date_filter').val('');
                    $('#to_date_filter').val('');
                }
            },
            onClose: function (selectedDates) {
                if (selectedDates.length === 1) {
                    meeTimePeriodPicker.clear();
                    $('#from_date_filter').val('');
                    $('#to_date_filter').val('');
                }
            }
        });
    }

    // +3 Filters active indicator
    function meeUpdateExtraFiltersIndicator() {
        var hasExtra = $('#year_filter').val() || $('#time_from_filter').val() || $('#time_to_filter').val();
        $('#meeExtraFiltersToggle').toggleClass('mee-more-filters-active', !!hasExtra);
    }
    meeUpdateExtraFiltersIndicator();

    $('#course_filter, #duty_type_filter, #year_filter').on('change', function() {
        meeUpdateExtraFiltersIndicator();
        table.ajax.reload();
    });
    $('#time_from_filter, #time_to_filter').on('change', function() {
        meeUpdateExtraFiltersIndicator();
        table.ajax.reload();
    });

    $('#resetFilters').on('click', function() {
        window.location.href = '{{ route("mdo-escrot-exemption.index", ["filter" => "active"]) }}';
    });

    // 🧱 Column Visibility modal (chips built from the live DataTable)
    var $meeColGrid = $('#meeColumnsGrid');
    table.columns().every(function(idx) {
        var title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
        var visible = this.visible();
        $meeColGrid.append(
            '<label class="mee-col-chip' + (visible ? ' is-checked' : '') + '" for="meeColToggle' + idx + '">' +
                '<input class="form-check-input mee-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                       'id="meeColToggle' + idx + '" data-column="' + idx + '">' +
                '<span>' + title + '</span>' +
            '</label>'
        );
    });
    $meeColGrid.on('change', '.mee-col-toggle', function() {
        table.column($(this).data('column')).visible(this.checked);
        $(this).closest('.mee-col-chip').toggleClass('is-checked', this.checked);
    });

    $('#mdoescot-table').on('preXhr.dt', function(e, settings, data) {
        data.filter = $('#filter_status').val() || 'active';
        data.course_filter = $('#course_filter').val();
        data.year_filter = $('#year_filter').val();
        data.duty_type_filter = $('#duty_type_filter').val();
        data.time_from_filter = $('#time_from_filter').val();
        data.time_to_filter = $('#time_to_filter').val();
        data.from_date_filter = $('#from_date_filter').val();
        data.to_date_filter = $('#to_date_filter').val();
    });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('#mdoescot-table form[id^="delete-form-"] button[type="submit"]');
        if (!btn) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        pendingDeleteForm = btn.closest('form');
        if (meeDeleteModal) {
            meeDeleteModal.show();
        } else if (window.confirm('Are you sure you want to delete this record?')) {
            pendingDeleteForm.submit();
        }
    }, true);

    $('#meeDeleteConfirmOk').on('click', function() {
        if (pendingDeleteForm) {
            pendingDeleteForm.off('submit');
            pendingDeleteForm[0].submit();
            pendingDeleteForm = null;
        }
        if (meeDeleteModal) {
            meeDeleteModal.hide();
        }
    });

    $('#meeDeleteConfirmCancel, #meeDeleteConfirmModal').on('hidden.bs.modal', function() {
        pendingDeleteForm = null;
    });

    function meeGetExportTableClone() {
        var tableClone = $('#mdoescot-table').clone();
        tableClone.find('th:last-child, td:last-child').remove();
        var html = tableClone[0].outerHTML;
        html = html.replace(/<th[^>]*>Actions<\/th>/gi, '');
        html = html.replace(/<td[^>]*>[\s\S]*?(edit|delete|Actions)[\s\S]*?<\/td>/gi, '');
        return html;
    }

    $('#printDownloadBtn').on('click', function() {
        var printWindow = window.open('', '_blank');
        var tableHtml = '<!DOCTYPE html><html><head><title>MDO/Escort Exemption</title>';
        tableHtml += '<style>';
        tableHtml += 'body { font-family: Arial, sans-serif; margin: 20px; }';
        tableHtml += 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
        tableHtml += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }';
        tableHtml += 'th { background-color: #b72a2a; color: white; font-weight: bold; }';
        tableHtml += 'tr:nth-child(even) { background-color: #f7f7f7; }';
        tableHtml += 'h2 { color: #004a93; margin-bottom: 20px; }';
        tableHtml += '@media print { body { margin: 0; } @page { margin: 1cm; } }';
        tableHtml += '</style></head><body>';
        tableHtml += '<h2>MDO/Escort Exemption</h2>';
        tableHtml += meeGetExportTableClone();
        tableHtml += '</body></html>';

        printWindow.document.write(tableHtml);
        printWindow.document.close();

        setTimeout(function() {
            printWindow.print();
        }, 250);
    });

    function initMeeAddModal(table) {
        var addModalEl = document.getElementById('meeAddModal');
        var studentModalEl = document.getElementById('meeStudentListModal');
        if (!addModalEl || !studentModalEl) {
            return;
        }

        var meeAddModal = bootstrap.Modal.getOrCreateInstance(addModalEl);
        var meeStudentModal = bootstrap.Modal.getOrCreateInstance(studentModalEl);
        var studentsUrl = @json(route('mdo-escrot-exemption.get.student.list.according.to.course'));
        var storeUrl = @json(route('mdo-escrot-exemption.store'));
        var updateUrl = @json(route('mdo-escrot-exemption.update'));
        var editDataBaseUrl = @json(url('mdo-escrot-exemption/edit-data'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        var meeModalMode = 'add';
        var meeAllStudents = [];
        var meeAssignedStudents = [];
        var meePickerSelectedIds = new Set();
        var meePickerStudentsMap = {};
        var meeStudentsRequest = null;
        var meeEscortDutyTypeId = null;

        $('#mdo_duty_type_master_pk option').each(function() {
            if ($(this).text().trim().toLowerCase() === 'escort') {
                meeEscortDutyTypeId = $(this).val();
            }
        });

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;');
        }

        function clearMeeFormErrors() {
            $('#meeAddFormAlert').addClass('d-none').removeClass('alert-success alert-danger').empty();
            $('#mdoDutyTypeForm .text-danger[id^="meeError"]').addClass('d-none');
            $('#mdoDutyTypeForm .form-select, #mdoDutyTypeForm .form-control').removeClass('is-invalid');
            $('#meeAssignStudentsTrigger').removeClass('is-invalid');
        }

        function showMeeFormError(message, errors) {
            var $alert = $('#meeAddFormAlert');
            $alert.removeClass('d-none alert-success').addClass('alert-danger').html(message);

            if (errors) {
                var map = {
                    course_master_pk: '#meeCourseDropdown',
                    mdo_duty_type_master_pk: '#mdo_duty_type_master_pk',
                    mdo_date: '#mdo_date',
                    Time_from: '#Time_from',
                    Time_to: '#Time_to',
                    faculty_master_pk: '#faculty_master_pk',
                    selected_student_list: '#meeAssignStudentsTrigger'
                };
                Object.keys(errors).forEach(function(key) {
                    var baseKey = key.split('.')[0];
                    var selector = map[baseKey] || map[key];
                    if (selector) {
                        $(selector).addClass('is-invalid');
                    }
                });
            }
        }

        function toggleFacultyField() {
            var dutyType = $('#mdo_duty_type_master_pk').val();
            if (meeEscortDutyTypeId && dutyType === meeEscortDutyTypeId) {
                $('#faculty_field_container').removeClass('d-none');
                $('#faculty_master_pk').prop('required', true);
            } else {
                $('#faculty_field_container').addClass('d-none');
                $('#faculty_master_pk').val('').prop('required', false);
            }
        }

        function syncHiddenStudentSelect() {
            var $hidden = $('#hiddenStudentSelect');
            $hidden.empty();
            meeAssignedStudents.forEach(function(student) {
                $hidden.append($('<option>', { value: student.pk, selected: true }));
            });
        }

        function renderAssignStudentTags() {
            var $tags = $('#meeAssignStudentsTags');
            var $label = $('#meeAssignStudentsLabel');
            $tags.empty();

            if (!meeAssignedStudents.length) {
                $label.text('Select Students').addClass('text-muted');
                $tags.addClass('d-none');
                syncHiddenStudentSelect();
                return;
            }

            $label.text('');
            $tags.removeClass('d-none');
            meeAssignedStudents.forEach(function(student) {
                $tags.append(
                    '<span class="badge rounded-1 mee-student-tag" data-student-id="' + student.pk + '">' +
                    escapeHtml(student.display_name) +
                    '<button type="button" class="btn-close btn-close-sm ms-1" aria-label="Remove ' + escapeHtml(student.display_name) + '"></button>' +
                    '</span>'
                );
            });
            syncHiddenStudentSelect();
        }

        function renderPickerTags() {
            var ids = Array.from(meePickerSelectedIds);
            $('#meeStudentSelectedCount').text(ids.length + ' Selected');
            var $tags = $('#meeStudentTags');
            $tags.empty();

            ids.forEach(function(id) {
                var student = meePickerStudentsMap[id];
                if (!student) {
                    return;
                }
                $tags.append(
                    '<span class="badge rounded-1 mee-student-tag" data-student-id="' + id + '">' +
                    escapeHtml(student.display_name) +
                    '<button type="button" class="btn-close btn-close-sm ms-1" aria-label="Remove"></button>' +
                    '</span>'
                );
            });
        }

        function renderStudentList() {
            var query = ($('#meeStudentListSearch').val() || '').trim().toLowerCase();
            var $list = $('#meeStudentList');
            var $empty = $('#meeStudentListEmpty');
            $list.empty();

            var filtered = meeAllStudents.filter(function(student) {
                if (!query) {
                    return true;
                }
                var name = (student.display_name || '').toLowerCase();
                var ot = (student.ot_code || '').toLowerCase();
                return name.indexOf(query) !== -1 || ot.indexOf(query) !== -1;
            });

            if (!filtered.length) {
                $list.addClass('d-none');
                $empty.removeClass('d-none').text(
                    meeAllStudents.length ? 'No students match your search.' : 'No students available for this course and date.'
                );
                return;
            }

            $empty.addClass('d-none');
            $list.removeClass('d-none');

            filtered.forEach(function(student) {
                var id = String(student.pk);
                var checked = meePickerSelectedIds.has(id) ? ' checked' : '';
                var otLabel = student.ot_code ? ' <span class="text-muted small">(' + escapeHtml(student.ot_code) + ')</span>' : '';
                $list.append(
                    '<li class="list-group-item mee-student-list-item">' +
                    '<div class="form-check d-flex align-items-center gap-2 mb-0">' +
                    '<input class="form-check-input mee-student-pick" type="checkbox" value="' + id + '" id="meeStudentPick_' + id + '"' + checked + '>' +
                    '<label class="form-check-label flex-grow-1" for="meeStudentPick_' + id + '">' + escapeHtml(student.display_name) + otLabel + '</label>' +
                    '</div></li>'
                );
            });
        }

        function setPickerFromAssigned() {
            meePickerSelectedIds.clear();
            meeAssignedStudents.forEach(function(student) {
                meePickerSelectedIds.add(String(student.pk));
            });
            renderPickerTags();
            renderStudentList();
        }

        function loadStudentsForPicker() {
            var courseId = $('#meeCourseDropdown').val();
            var selectedDate = $('#mdo_date').val();

            if (!courseId || !selectedDate) {
                return $.Deferred().reject().promise();
            }

            if (meeStudentsRequest && typeof meeStudentsRequest.abort === 'function') {
                meeStudentsRequest.abort();
            }

            $('#meeStudentListEmpty').removeClass('d-none').text('Loading students...');
            $('#meeStudentList').addClass('d-none');

            meeStudentsRequest = $.ajax({
                url: studentsUrl,
                type: 'POST',
                data: {
                    _token: csrfToken,
                    selectedCourses: courseId,
                    selectedDate: selectedDate
                }
            });

            return meeStudentsRequest.then(function(response) {
                if (!response.status) {
                    meeAllStudents = [];
                    $('#meeStudentListEmpty').removeClass('d-none').text(response.message || 'Unable to load students.');
                    $('#meeStudentList').addClass('d-none');
                    return;
                }

                meeAllStudents = response.students || [];
                meePickerStudentsMap = {};
                meeAllStudents.forEach(function(s) {
                    meePickerStudentsMap[String(s.pk)] = s;
                });
                setPickerFromAssigned();
            }).always(function() {
                meeStudentsRequest = null;
            });
        }

        function prepareAddMode() {
            meeModalMode = 'add';
            $('#meeAddModalLabel').text('Add MDO/ Escort Exemption');
            $('#meeAddSubmitBtn').text('Add MDO/ Escort Exemption');
            $('#mdoDutyTypeForm').attr('action', storeUrl);
            $('#meeRecordPk').val('');
            $('.mee-add-only-field').removeClass('d-none');
            $('#meeEditStudentInfo').addClass('d-none').removeClass('d-flex');
            $('#meeCourseDropdown').prop('required', true).prop('disabled', false);
            $('#meeAssignStudentsTrigger').prop('disabled', false);
        }

        function prepareEditMode() {
            meeModalMode = 'edit';
            $('#meeAddModalLabel').text('Edit MDO/ Escort Exemption');
            $('#meeAddSubmitBtn').text('Update MDO/ Escort Exemption');
            $('#mdoDutyTypeForm').attr('action', updateUrl);
            $('.mee-add-only-field').addClass('d-none');
            $('#meeEditStudentInfo').removeClass('d-none').addClass('d-flex');
            $('#meeCourseDropdown').prop('required', false).prop('disabled', true);
            $('#meeAssignStudentsTrigger').prop('disabled', true);
        }

        function resetMeeAddForm() {
            var form = document.getElementById('mdoDutyTypeForm');
            if (form) {
                form.reset();
            }
            meeAllStudents = [];
            meeAssignedStudents = [];
            meePickerSelectedIds.clear();
            meePickerStudentsMap = {};
            $('#meeStudentListSearch').val('');
            $('#hiddenStudentSelect').empty();
            $('#meeRecordPk').val('');
            $('#meeEditStudentName, #meeEditCourseName').text('—');
            toggleFacultyField();
            renderAssignStudentTags();
            renderPickerTags();
            $('#meeStudentListEmpty').removeClass('d-none').text('Select course and start date to load students.');
            $('#meeStudentList').addClass('d-none').empty();
            clearMeeFormErrors();
            prepareAddMode();
        }

        function validateMeeForm() {
            clearMeeFormErrors();
            var valid = true;
            var isEdit = meeModalMode === 'edit';

            if (!isEdit && !$('#meeCourseDropdown').val()) {
                $('#meeErrorCourse').removeClass('d-none');
                $('#meeCourseDropdown').addClass('is-invalid');
                valid = false;
            }
            if (!$('#mdo_duty_type_master_pk').val()) {
                $('#meeErrorDutyType').removeClass('d-none');
                $('#mdo_duty_type_master_pk').addClass('is-invalid');
                valid = false;
            }
            if (!$('#mdo_date').val()) {
                $('#meeErrorDate').removeClass('d-none');
                $('#mdo_date').addClass('is-invalid');
                valid = false;
            }
            if (!$('#Time_from').val()) {
                $('#meeErrorTimeFrom').removeClass('d-none');
                $('#Time_from').addClass('is-invalid');
                valid = false;
            }
            if (!$('#Time_to').val()) {
                $('#meeErrorTimeTo').removeClass('d-none');
                $('#Time_to').addClass('is-invalid');
                valid = false;
            }
            if ($('#Time_from').val() && $('#Time_to').val() && $('#Time_to').val() <= $('#Time_from').val()) {
                $('#meeErrorTimeTo').removeClass('d-none').text('End time must be after start time.');
                $('#Time_to').addClass('is-invalid');
                valid = false;
            }
            if (meeEscortDutyTypeId && $('#mdo_duty_type_master_pk').val() === meeEscortDutyTypeId && !$('#faculty_master_pk').val()) {
                $('#meeErrorFaculty').removeClass('d-none');
                $('#faculty_master_pk').addClass('is-invalid');
                valid = false;
            }
            if (!isEdit && !meeAssignedStudents.length) {
                $('#meeErrorStudents').removeClass('d-none');
                $('#meeAssignStudentsTrigger').addClass('is-invalid');
                valid = false;
            }

            return valid;
        }

        $('#meeAddExemptionBtn').on('click', function() {
            resetMeeAddForm();
            meeAddModal.show();
        });

        // Editing is handled by the dedicated #meeEditModal (see initMeeEditModal).

        addModalEl.addEventListener('hidden.bs.modal', function() {
            resetMeeAddForm();
        });

        $('#mdo_duty_type_master_pk').on('change', toggleFacultyField);

        $('#meeCourseDropdown, #mdo_date').on('change', function() {
            meeAssignedStudents = [];
            meeAllStudents = [];
            renderAssignStudentTags();
        });

        $('#meeAssignStudentsTrigger').on('click', function() {
            if (meeModalMode === 'edit') {
                return;
            }
            if (!$('#meeCourseDropdown').val()) {
                $('#meeErrorCourse').removeClass('d-none');
                $('#meeCourseDropdown').addClass('is-invalid').focus();
                return;
            }
            if (!$('#mdo_date').val()) {
                $('#meeErrorDate').removeClass('d-none');
                $('#mdo_date').addClass('is-invalid').focus();
                return;
            }

            setPickerFromAssigned();
            loadStudentsForPicker().always(function() {
                meeStudentModal.show();
            });
        });

        $('#meeStudentListSearch').on('input', renderStudentList);

        $(document).on('change', '#meeStudentList .mee-student-pick', function() {
            var id = String($(this).val());
            if (this.checked) {
                meePickerSelectedIds.add(id);
            } else {
                meePickerSelectedIds.delete(id);
            }
            renderPickerTags();
        });

        $('#meeStudentTags').on('click', '.btn-close', function() {
            var id = String($(this).closest('[data-student-id]').data('student-id'));
            meePickerSelectedIds.delete(id);
            $('.mee-student-pick[value="' + id + '"]').prop('checked', false);
            renderPickerTags();
        });

        $('#meeAssignStudentsTags').on('click', '.btn-close', function() {
            var id = String($(this).closest('[data-student-id]').data('student-id'));
            meeAssignedStudents = meeAssignedStudents.filter(function(s) {
                return String(s.pk) !== id;
            });
            renderAssignStudentTags();
        });

        $('#meeStudentClearAll').on('click', function() {
            meePickerSelectedIds.clear();
            $('.mee-student-pick').prop('checked', false);
            renderPickerTags();
        });

        $('#meeStudentSelectAll').on('click', function() {
            $('#meeStudentList .mee-student-pick:visible').each(function() {
                meePickerSelectedIds.add(String($(this).val()));
                $(this).prop('checked', true);
            });
            renderPickerTags();
        });

        $('#meeStudentSave').on('click', function() {
            var ids = Array.from(meePickerSelectedIds);
            if (!ids.length) {
                Swal.fire('Required', 'Please select at least one student.', 'warning');
                return;
            }
            meeAssignedStudents = ids.map(function(id) {
                return meePickerStudentsMap[id];
            }).filter(Boolean);
            renderAssignStudentTags();
            $('#meeErrorStudents').addClass('d-none');
            $('#meeAssignStudentsTrigger').removeClass('is-invalid');
            meeStudentModal.hide();
        });

        $('#mdoDutyTypeForm').on('submit', function(e) {
            e.preventDefault();

            if (!validateMeeForm()) {
                return;
            }

            var $submit = $('#meeAddSubmitBtn');
            var defaultText = $submit.text();
            var formData = new FormData(this);

            $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    meeAddModal.hide();
                    table.ajax.reload(null, false);
                    var defaultMsg = meeModalMode === 'edit'
                        ? 'MDO/Escort Exemption updated successfully.'
                        : 'MDO/Escort Exemption created successfully.';
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: (response && response.message) ? response.message : defaultMsg,
                        timer: 2200,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    var message = 'Something went wrong. Please try again.';
                    var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (errors) {
                        message = Object.values(errors).flat().join('<br>');
                    }

                    showMeeFormError(message, errors);
                },
                complete: function() {
                    $submit.prop('disabled', false).text(defaultText);
                }
            });
        });
    }

    // ===== Dedicated Edit modal (separate from Add) =====
    function initMeeEditModal(table) {
        var editModalEl = document.getElementById('meeEditModal');
        if (!editModalEl) {
            return;
        }

        var meeEditModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
        var updateUrl = @json(route('mdo-escrot-exemption.update'));
        var editDataBaseUrl = @json(url('mdo-escrot-exemption/edit-data'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        // Resolve the "Escort" duty-type id from the edit modal's own select
        var editEscortDutyTypeId = null;
        $('#meeEditDutyType option').each(function() {
            if ($(this).text().trim().toLowerCase() === 'escort') {
                editEscortDutyTypeId = $(this).val();
            }
        });

        function clearEditErrors() {
            $('#meeEditFormAlert').addClass('d-none').removeClass('alert-success alert-danger').empty();
            $('#meeEditForm .text-danger[id^="meeEditError"]').addClass('d-none');
            $('#meeEditForm .form-select, #meeEditForm .form-control').removeClass('is-invalid');
        }

        function toggleEditFaculty() {
            var dutyType = $('#meeEditDutyType').val();
            if (editEscortDutyTypeId && dutyType === editEscortDutyTypeId) {
                $('#meeEditFacultyContainer').removeClass('d-none');
                $('#meeEditFaculty').prop('required', true);
            } else {
                $('#meeEditFacultyContainer').addClass('d-none');
                $('#meeEditFaculty').prop('required', false);
            }
        }

        function validateEditForm() {
            clearEditErrors();
            var valid = true;
            if (!$('#meeEditDutyType').val()) { $('#meeEditErrorDutyType').removeClass('d-none'); $('#meeEditDutyType').addClass('is-invalid'); valid = false; }
            if (!$('#meeEditDate').val()) { $('#meeEditErrorDate').removeClass('d-none'); $('#meeEditDate').addClass('is-invalid'); valid = false; }
            if (!$('#meeEditTimeFrom').val()) { $('#meeEditErrorTimeFrom').removeClass('d-none'); $('#meeEditTimeFrom').addClass('is-invalid'); valid = false; }
            if (!$('#meeEditTimeTo').val()) { $('#meeEditErrorTimeTo').removeClass('d-none'); $('#meeEditTimeTo').addClass('is-invalid'); valid = false; }
            if ($('#meeEditTimeFrom').val() && $('#meeEditTimeTo').val() && $('#meeEditTimeTo').val() <= $('#meeEditTimeFrom').val()) {
                $('#meeEditErrorTimeTo').removeClass('d-none').text('End time must be after start time.');
                $('#meeEditTimeTo').addClass('is-invalid'); valid = false;
            }
            if (editEscortDutyTypeId && $('#meeEditDutyType').val() === editEscortDutyTypeId && !$('#meeEditFaculty').val()) {
                $('#meeEditErrorFaculty').removeClass('d-none'); $('#meeEditFaculty').addClass('is-invalid'); valid = false;
            }
            return valid;
        }

        $('#meeEditDutyType').on('change', toggleEditFaculty);

        // Open + populate from edit-data endpoint
        $(document).on('click', '.mee-edit-btn', function(e) {
            e.preventDefault();
            var editId = $(this).data('edit-id');
            if (!editId) { return; }

            clearEditErrors();
            var $btn = $(this).prop('disabled', true);

            $.ajax({
                url: editDataBaseUrl + '/' + editId,
                type: 'GET',
                headers: { 'Accept': 'application/json' },
                success: function(res) {
                    var record = res.record || {};
                    $('#meeEditRecordPk').val(record.pk);
                    $('#meeEditDutyType').val(record.mdo_duty_type_master_pk);
                    $('#meeEditDate').val(record.mdo_date || '');
                    $('#meeEditTimeFrom').val(record.Time_from || '');
                    $('#meeEditTimeTo').val(record.Time_to || '');
                    $('#meeEditFaculty').val(record.faculty_master_pk || '');
                    $('#meeEditStudentDisplay').text(record.student_name || '—');
                    $('#meeEditCourseDisplay').text(record.course_name || '—');
                    toggleEditFaculty();
                    meeEditModal.show();
                },
                error: function() {
                    Swal.fire('Error', 'Unable to load record for editing.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        $('#meeEditForm').on('submit', function(e) {
            e.preventDefault();
            if (!validateEditForm()) { return; }

            var $submit = $('#meeEditSubmitBtn');
            var defaultText = $submit.text();
            var formData = new FormData(this);

            $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(response) {
                    meeEditModal.hide();
                    table.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: (response && response.message) ? response.message : 'MDO/Escort Exemption updated successfully.',
                        timer: 2200,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    var message = 'Something went wrong. Please try again.';
                    var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (errors) {
                        message = Object.values(errors).flat().join('<br>');
                    }
                    $('#meeEditFormAlert').removeClass('d-none alert-success').addClass('alert-danger').html(message);
                },
                complete: function() {
                    $submit.prop('disabled', false).text(defaultText);
                }
            });
        });
    }

    // ===== Bulk Upload modal =====
    function initMeeBulkUploadModal(table) {
        var bulkModalEl = document.getElementById('meeBulkUploadModal');
        if (!bulkModalEl) {
            return;
        }

        var meeBulkModal = bootstrap.Modal.getOrCreateInstance(bulkModalEl);
        var bulkStoreUrl = @json(route('mdo-escrot-exemption.bulk.store'));
        var bulkTemplateUrl = @json(route('mdo-escrot-exemption.bulk.template'));

        // Resolve the "Escort" duty-type id from the bulk modal's own select
        var bulkEscortDutyTypeId = null;
        $('#meeBulkDutyType option').each(function() {
            if ($(this).text().trim().toLowerCase() === 'escort') {
                bulkEscortDutyTypeId = $(this).val();
            }
        });

        function toggleBulkFaculty() {
            var dutyType = $('#meeBulkDutyType').val();
            if (bulkEscortDutyTypeId && dutyType === bulkEscortDutyTypeId) {
                $('#meeBulkFacultyContainer').removeClass('d-none');
                $('#meeBulkFaculty').prop('required', true);
            } else {
                $('#meeBulkFacultyContainer').addClass('d-none');
                $('#meeBulkFaculty').val('').prop('required', false);
            }
        }

        function clearBulkErrors() {
            $('#meeBulkFormAlert').addClass('d-none').removeClass('alert-success alert-danger').empty();
            $('#meeBulkUploadForm .text-danger[id^="meeBulkError"]').addClass('d-none');
            $('#meeBulkUploadForm .form-select, #meeBulkUploadForm .form-control').removeClass('is-invalid');
            $('#meeBulkResultBox').addClass('d-none');
            $('#meeBulkResultErrors').addClass('d-none');
            $('#meeBulkResultErrorList').empty();
        }

        function resetBulkForm() {
            var form = document.getElementById('meeBulkUploadForm');
            if (form) {
                form.reset();
            }
            clearBulkErrors();
            toggleBulkFaculty();
        }

        function validateBulkForm() {
            clearBulkErrors();
            var valid = true;
            if (!$('#meeBulkCourse').val()) {
                $('#meeBulkErrorCourse').removeClass('d-none');
                $('#meeBulkCourse').addClass('is-invalid');
                valid = false;
            }
            if (!$('#meeBulkDutyType').val()) {
                $('#meeBulkErrorDutyType').removeClass('d-none');
                $('#meeBulkDutyType').addClass('is-invalid');
                valid = false;
            }
            if (bulkEscortDutyTypeId && $('#meeBulkDutyType').val() === bulkEscortDutyTypeId && !$('#meeBulkFaculty').val()) {
                $('#meeBulkErrorFaculty').removeClass('d-none');
                $('#meeBulkFaculty').addClass('is-invalid');
                valid = false;
            }
            if (!$('#meeBulkFile').val()) {
                $('#meeBulkErrorFile').removeClass('d-none');
                $('#meeBulkFile').addClass('is-invalid');
                valid = false;
            }
            return valid;
        }

        function renderBulkResult(response) {
            var $box = $('#meeBulkResultBox').removeClass('d-none');
            var imported = response.imported || 0;
            var skipped = response.skipped || 0;
            $('#meeBulkResultSummary').text(imported + ' imported, ' + skipped + ' skipped.');

            var errors = response.errors || [];
            if (errors.length) {
                var $list = $('#meeBulkResultErrorList').empty();
                errors.forEach(function(err) {
                    $list.append($('<li>').text(err));
                });
                $('#meeBulkResultErrors').removeClass('d-none');
            } else {
                $('#meeBulkResultErrors').addClass('d-none');
            }
        }

        $('#meeBulkUploadBtn').on('click', function() {
            resetBulkForm();
            meeBulkModal.show();
        });

        bulkModalEl.addEventListener('hidden.bs.modal', resetBulkForm);

        $('#meeBulkDutyType').on('change', toggleBulkFaculty);

        $('#meeBulkDownloadTemplate').on('click', function(e) {
            e.preventDefault();
            var courseId = $('#meeBulkCourse').val();
            var url = bulkTemplateUrl + (courseId ? ('?course_master_pk=' + encodeURIComponent(courseId)) : '');
            window.location.href = url;
        });

        $('#meeBulkUploadForm').on('submit', function(e) {
            e.preventDefault();
            if (!validateBulkForm()) {
                return;
            }

            var $submit = $('#meeBulkSubmitBtn');
            var defaultText = $submit.text();
            var formData = new FormData(this);

            $submit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Uploading...');

            // Show the file upload progress loader
            var $uploadProgress = $('#meeBulkUploadProgress').removeClass('d-none');
            var $uploadBar = $('#meeBulkUploadBar').css('width', '0%').attr('aria-valuenow', 0);
            $('#meeBulkUploadPercent').text('0%');

            $.ajax({
                url: bulkStoreUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                xhr: function() {
                    var xhr = $.ajaxSettings.xhr();
                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                var pct = Math.round((e.loaded / e.total) * 100);
                                $uploadBar.css('width', pct + '%').attr('aria-valuenow', pct);
                                $('#meeBulkUploadPercent').text(pct + '%');
                            }
                        }, false);
                    }
                    return xhr;
                },
                success: function(response) {
                    table.ajax.reload(null, false);
                    renderBulkResult(response);
                    $('#meeBulkFormAlert').removeClass('d-none alert-danger').addClass('alert-success')
                        .text(response.message || 'Upload completed.');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || 'Bulk upload completed.',
                        timer: 2400,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    var response = xhr.responseJSON || {};
                    var message = response.message || 'Something went wrong. Please try again.';
                    var errors = response.errors || null;

                    if (errors && !response.imported) {
                        // Laravel validation errors (object of field => messages)
                        if (!Array.isArray(errors)) {
                            var map = {
                                course_master_pk: '#meeBulkCourse',
                                mdo_duty_type_master_pk: '#meeBulkDutyType',
                                faculty_master_pk: '#meeBulkFaculty',
                                bulk_file: '#meeBulkFile'
                            };
                            Object.keys(errors).forEach(function(key) {
                                if (map[key]) {
                                    $(map[key]).addClass('is-invalid');
                                }
                            });
                            message = Object.values(errors).flat().join('<br>');
                        }
                    }

                    $('#meeBulkFormAlert').removeClass('d-none alert-success').addClass('alert-danger').html(message);

                    // Row-level errors from the importer (file processed but nothing imported)
                    if (response.errors && Array.isArray(response.errors)) {
                        renderBulkResult(response);
                    }
                },
                complete: function() {
                    $('#meeBulkUploadProgress').addClass('d-none');
                    $submit.prop('disabled', false).text(defaultText);
                }
            });
        });
    }

    $('#downloadBtn').on('click', function() {
        var $table = $('<div>').html(meeGetExportTableClone()).find('table');
        if (!$table.length) {
            return;
        }

        var rows = [];
        $table.find('tr').each(function() {
            var cells = [];
            $(this).find('th, td').each(function() {
                var text = $(this).text().replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                cells.push('"' + text + '"');
            });
            if (cells.length) {
                rows.push(cells.join(','));
            }
        });

        if (!rows.length) {
            return;
        }

        var csv = rows.join('\r\n');
        var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'MDO_Escort_Exemption_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });
});
</script>
@endpush
