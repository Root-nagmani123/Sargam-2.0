@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
/* Escort / Moderator Duty — page-only pieces the shared mst-* layer does not
 * cover. Scoped to the page root (.mee-page) and to this page's modals
 * (.mee-modal), built on the --ds-* tokens (docs/design.md). */

/* Time Period chip: flatpickr's altInput copies these classes, so the icon
 * sits over whichever input is visible. */
.mst-page.mee-page .mee-time-period-filter { position: relative; }
.mst-page.mee-page .mee-tp-input {
    padding-left: calc(var(--ds-space-3) * 2.25);
    cursor: pointer;
}
.mst-page.mee-page .mee-tp-ico {
    position: absolute;
    left: var(--ds-space-3);
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-ink-muted);
    pointer-events: none;
    z-index: 1;
}

/* Long-text columns (Student / Programme / Faculty / Remarks) wrap via
 * .mst-col-wrap; give them a floor so an 11-column grid scrolls sideways in
 * .table-responsive instead of squeezing those cells to one letter per line. */
.mst-page.mee-page #mdoescot-table .mst-col-wrap { min-width: 11rem; }

/* "+3 Filters" overflow menu */
.mst-page.mee-page .mee-more-filters.mee-more-filters-active { font-weight: 700; }
.mst-page.mee-page .mee-extra-menu { min-width: 16rem; }

/* This grid keeps its Yajra `dom` (pager + "Showing … of N items" row); the
 * page moves those nodes into the programme-dt-footer below the table, so the
 * now-empty row is hidden rather than left as a gap. */
.mst-page.mee-page .mee-dt-bottom.mee-dt-bottom--moved { display: none; }

/* Column-visibility chips — the checked state is part of the existing JS. */
.mee-col-grid .mee-col-chip.is-checked { border-color: var(--ds-primary) !important; }

/* Add / Edit / Bulk modals wrap header, body and footer in a <form>, which
 * breaks Bootstrap's .modal-dialog-scrollable (it expects them as direct
 * children of .modal-content). Make the form a flex column so the body scrolls
 * and the footer stays pinned, even with a long "skipped rows" list. */
.mee-modal .modal-dialog-scrollable .modal-content > form {
    display: flex;
    flex-direction: column;
    max-height: 100%;
    overflow: hidden;
}
.mee-modal .modal-dialog-scrollable .modal-content > form > .modal-body { overflow-y: auto; }
.mee-modal .modal-dialog-scrollable .modal-content > form > .modal-header,
.mee-modal .modal-dialog-scrollable .modal-content > form > .modal-footer { flex-shrink: 0; }

/* Select2 dropdowns opened from a modal stay above it. */
.select2-container--open { z-index: 1060; }

.mee-modal .mee-field-error {
    display: block;
    margin-top: var(--ds-space-1);
    font-size: 0.8125rem;
}
.mee-modal .mee-hint {
    display: block;
    margin-top: var(--ds-space-1);
    font-size: 0.8125rem;
    color: var(--ds-ink-muted);
}

/* Assign Students: a button styled as a select, with the picked names as tags. */
.mee-modal .mee-assign-students-trigger {
    width: 100%;
    text-align: left;
}
.mee-modal .mee-student-tag {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1);
    background-color: var(--ds-primary);
    color: var(--ds-surface);
    padding: var(--ds-space-1) var(--ds-space-2);
    font-weight: 500;
}
.mee-modal .mee-student-tag .btn-close { font-size: 0.55rem; }

/* Student picker */
.mee-modal .mee-student-search { position: relative; }
.mee-modal .mee-student-search-icon {
    position: absolute;
    left: var(--ds-space-3);
    top: 50%;
    transform: translateY(-50%);
    color: var(--ds-ink-muted);
    pointer-events: none;
}
.mee-modal .mee-student-search-input { padding-left: calc(var(--ds-space-3) * 2.25); }
.mee-modal .mee-student-selected-divider {
    align-self: stretch;
    width: 1px;
    background: var(--ds-line);
}
.mee-modal .mee-student-list-wrap {
    background: var(--ds-surface);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-card);
    max-height: 22rem;
    overflow-y: auto;
}

/* Bulk upload */
.mee-modal .mee-progress { height: calc(var(--ds-space-1) * 1.5); }
.mee-modal .mee-bulk-errors {
    max-height: 12.5rem;
    overflow-y: auto;
}
.mee-modal .mee-bulk-result {
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-card);
    background: var(--ds-surface);
    padding: var(--ds-space-3);
}
</style>
@endpush

@section('setup_content')
<div class="container-fluid mst-page mee-page mee-master-page">
    <x-breadcrum title="Escort/ Moderator Duty" :showBack="false">
        <div class="d-inline-flex flex-wrap align-items-center gap-2">
            <button type="button"
                id="meeBulkUploadBtn"
                class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm text-nowrap">
                <i class="bi bi-upload" aria-hidden="true"></i>
                <span>Bulk Upload</span>
            </button>
            <button type="button"
                id="meeAddExemptionBtn"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm text-nowrap">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
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
        $meeScope = $filter ?? 'active';
    @endphp

    {{-- Course scope (links: each is its own ?filter=) left · Download / Print
         right — above the card (docs/new-design-index-page.md §1). Both
         exports fetch the full filtered set from the grid's own feed. --}}
    <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Course status filter">
            <li class="nav-item" role="presentation">
                <a href="{{ route('mdo-escrot-exemption.index', $activeParams) }}"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $meeScope === 'active' ? 'active' : '' }}"
                    id="filterActive"
                    aria-pressed="{{ $meeScope === 'active' ? 'true' : 'false' }}"
                    @if ($meeScope === 'active') aria-current="true" @endif>
                    Active
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ route('mdo-escrot-exemption.index', $archiveParams) }}"
                    class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $meeScope === 'archive' ? 'active' : '' }}"
                    id="filterArchive"
                    aria-pressed="{{ $meeScope === 'archive' ? 'true' : 'false' }}"
                    @if ($meeScope === 'archive') aria-current="true" @endif>
                    Archived
                </a>
            </li>
        </ul>

        <div class="d-flex flex-wrap justify-content-lg-end gap-2 mst-secondary-actions">
            <button type="button" id="downloadBtn"
                class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="printDownloadBtn"
                class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    {{-- No overflow-hidden: the "+3 Filters" menu (and the Year list inside it)
         is positioned within this card and would be clipped when the grid is short. --}}
    <div class="card rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row flex-lg-wrap align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                {{-- LEFT: filters. Each one reloads the grid through preXhr.dt. --}}
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select id="course_filter" class="form-select mst-control mst-searchable"
                            data-placeholder="Course Name" aria-label="Filter by course name">
                            <option value="">Course Name</option>
                            @foreach ($courseMaster as $id => $name)
                            <option value="{{ $id }}" {{ (string) request('course_filter') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="duty_type_filter" class="form-select mst-control mst-searchable"
                            data-placeholder="Duty Type" aria-label="Filter by duty type">
                            <option value="">Duty Type</option>
                            @foreach ($dutyTypes as $id => $name)
                            <option value="{{ $id }}" {{ (string) request('duty_type_filter') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Time Period (flatpickr range) --}}
                    <div class="programme-dt-filter-select mee-time-period-filter">
                        <input type="hidden" id="from_date_filter" value="{{ request('from_date_filter') }}">
                        <input type="hidden" id="to_date_filter" value="{{ request('to_date_filter') }}">
                        <i class="bi bi-calendar3 mee-tp-ico" aria-hidden="true"></i>
                        <input type="text" id="mee_time_period_picker"
                            class="form-control mst-control mee-tp-input"
                            placeholder="Time Period" value="{{ $timePeriodLabel }}"
                            readonly autocomplete="off" aria-label="Filter by time period">
                    </div>

                    {{-- +3 Filters (Year, Time From, Time To) --}}
                    <div class="dropdown">
                        <button type="button" class="btn btn-link p-0 fw-semibold text-primary text-decoration-underline mee-more-filters"
                            id="meeExtraFiltersToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            +3 Filters
                        </button>
                        <div class="dropdown-menu p-3 shadow-sm border rounded-3 mee-extra-menu" id="meeExtraFiltersMenu"
                            aria-labelledby="meeExtraFiltersToggle">
                            <h2 class="h6 fw-semibold mb-0">More filters</h2>
                            <hr class="my-3 opacity-50">
                            <div class="d-flex flex-column gap-3">
                                <div>
                                    <label for="year_filter" class="mst-form-label d-block">Year</label>
                                    {{-- Select2 is initialised by the page script with this menu as its
                                         dropdownParent, so picking a year doesn't close the menu. --}}
                                    <select id="year_filter" class="form-select mst-control" data-placeholder="Year">
                                        <option value="">Year</option>
                                        @foreach ($years as $year => $yearValue)
                                        <option value="{{ $year }}" {{ (string) request('year_filter') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="time_from_filter" class="mst-form-label d-block">Time From</label>
                                    <input type="time" id="time_from_filter" class="form-control mst-control"
                                        value="{{ request('time_from_filter') }}">
                                </div>
                                <div>
                                    <label for="time_to_filter" class="mst-form-label d-block">Time To</label>
                                    <input type="time" id="time_to_filter" class="form-control mst-control"
                                        value="{{ request('time_to_filter') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">Reset Filters</button>
                </div>

                {{-- RIGHT: columns + search. The grid's Yajra `dom` has no filter
                     element, so this box drives table.search() itself. --}}
                <div class="d-flex flex-wrap flex-sm-nowrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="meeColumnsToggle"
                        data-bs-toggle="modal" data-bs-target="#meeColumnsModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search">
                        <div class="dataTables_filter">
                            <label class="mb-0">
                                <span class="visually-hidden">Search records</span>
                                <input type="search" id="meeTableSearch" class="form-control shadow-none"
                                    placeholder="Search" autocomplete="off" aria-label="Search records">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="filter_status" value="{{ $filter ?? 'active' }}">

            <div class="programme-dt-panel mee-dt-panel">
                <div class="table-responsive mee-dt-scroll">
                    {{-- Opted out of datatable-global-ui.js: this grid's Yajra `dom`
                         renders its own pager row. The page script moves those
                         nodes into #meeDtFooter (same footer the enhancer builds). --}}
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table', 'data-sargam-dt-ui' => 'false']) !!}
                </div>
                <div id="meeDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.mdo_escrot_exemption.partials.add_modal')
@include('admin.mdo_escrot_exemption.partials.edit_modal')
@include('admin.mdo_escrot_exemption.partials.student_list_modal')
@include('admin.mdo_escrot_exemption.partials.bulk_upload_modal')

{{-- Column Visibility --}}
<div class="modal fade" id="meeColumnsModal" tabindex="-1" aria-labelledby="meeColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="meeColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid mee-col-grid" id="meeColumnsGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete confirmation (shared programme-confirm component) --}}
<div class="modal fade programme-confirm-modal-root" id="meeDeleteConfirmModal" tabindex="-1"
    aria-labelledby="meeDeleteConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered programme-confirm-dialog">
        <div class="modal-content programme-confirm-modal border-0 shadow-lg rounded-3 overflow-hidden">
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
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
$(document).ready(function() {
    var table = $('#mdoescot-table').DataTable();
    var meeTimePeriodPicker = null;
    var pendingDeleteForm = null;
    var meeDeleteModalEl = document.getElementById('meeDeleteConfirmModal');
    var meeDeleteModal = meeDeleteModalEl ? bootstrap.Modal.getOrCreateInstance(meeDeleteModalEl) : null;

    // A date input accepts a half-typed year (0025 for 2025); the server rejects
    // anything outside 2000–2099, so catch it here with the field's own error.
    function meeCheckDate($input, $error) {
        var value = $input.val();
        var year = value ? parseInt(value.slice(0, 4), 10) : NaN;
        if (value && year >= 2000 && year <= 2099) {
            return true;
        }
        $error.text(value ? 'Please enter a valid date (check the year).' : 'Start date is required.').removeClass('d-none');
        $input.addClass('is-invalid');
        return false;
    }

    initMeeAddModal(table);
    initMeeEditModal(table);
    initMeeBulkUploadModal(table);

    function meeBindDeleteActions() {
        $('#mdoescot-table form.mee-delete-form button[type="submit"]').removeAttr('onclick');
    }

    // This grid opts out of datatable-global-ui.js (its Yajra `dom` renders
    // the pager + "Showing [n] of N items" row itself), so move those DataTables
    // nodes into the programme-dt footer under the table. Moving keeps
    // DataTables' own references, so every redraw still updates them.
    function meeRelocateFooter() {
        var $footer = $('#meeDtFooter');
        var $wrapper = $('#mdoescot-table_wrapper');
        if (!$footer.length || !$wrapper.length || $footer.find('.dataTables_paginate').length) {
            return;
        }
        var $paginate = $wrapper.find('.dataTables_paginate').first();
        var $length = $wrapper.find('.dataTables_length').first();
        var $info = $wrapper.find('.dataTables_info').first();
        if (!$paginate.length && !$length.length && !$info.length) {
            return;
        }
        $length.find('select').addClass('form-select form-select-sm');
        $info.addClass('mb-0');
        $footer.empty()
            .append($('<div class="programme-dt-pagination"></div>').append($paginate))
            .append($('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>').append($length).append($info));
        $wrapper.find('.mee-dt-bottom').addClass('mee-dt-bottom--moved');
    }
    meeRelocateFooter();

    table.on('draw.dt', function() {
        var info = table.page.info();
        $('#total-records-count').text(info.recordsFiltered || info.recordsTotal || 0);
        meeRelocateFooter();
        meeBindDeleteActions();
    });

    meeBindDeleteActions();

    // 🔍 Search box (toolbar slot) → server-side global search
    var meeSearchTimer;
    $('#meeTableSearch').on('keyup search', function() {
        var value = this.value;
        clearTimeout(meeSearchTimer);
        meeSearchTimer = setTimeout(function() {
            table.search(value).draw();
        }, 400);
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
            onReady: function (_d, _s, instance) {
                instance.calendarContainer.classList.add('mee-flatpickr-theme');
                // altInput is the field users actually see; give it the label too.
                if (instance.altInput) { instance.altInput.setAttribute('aria-label', 'Filter by time period'); }
            },
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

    // Year sits inside the "+3 Filters" dropdown menu: Select2 must render its
    // results inside that menu, or Bootstrap treats a pick as an outside click
    // and closes the menu. (Course / Duty Type use the shared .mst-searchable.)
    if ($.fn.select2) {
        $('#year_filter').select2({
            width: '100%',
            placeholder: $('#year_filter').data('placeholder') || 'Year',
            allowClear: false,
            dropdownParent: $('#meeExtraFiltersMenu')
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
            '<div class="col-12 col-sm-6 col-md-4">' +
            '<label class="colvis-item mee-col-chip d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100' + (visible ? ' is-checked' : '') + '" for="meeColToggle' + idx + '">' +
                '<input class="form-check-input mee-col-toggle m-0" type="checkbox" ' + (visible ? 'checked ' : '') +
                       'id="meeColToggle' + idx + '" data-column="' + idx + '">' +
                '<span>' + title + '</span>' +
            '</label>' +
            '</div>'
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
        var btn = e.target.closest('#mdoescot-table form.mee-delete-form button[type="submit"]');
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
            // pendingDeleteForm is a native <form> element; submit it natively.
            pendingDeleteForm.submit();
            pendingDeleteForm = null;
        }
        if (meeDeleteModal) {
            meeDeleteModal.hide();
        }
    });

    $('#meeDeleteConfirmCancel, #meeDeleteConfirmModal').on('hidden.bs.modal', function() {
        pendingDeleteForm = null;
    });

    // Export columns = every visible table column except the "Action" column.
    function meeExportColumns() {
        var cols = [];
        (table.settings()[0].aoColumns || []).forEach(function(c, idx) {
            if (c.data === 'actions' || !table.column(idx).visible()) {
                return;
            }
            cols.push({ data: c.data, title: $('<div>').html(c.sTitle || '').text() });
        });
        return cols;
    }

    // Strip HTML/entities from a rendered cell value for plain-text export.
    function meeExportCellText(value) {
        return $('<div>').html(value == null ? '' : String(value)).text()
            .replace(/\s+/g, ' ').trim();
    }

    // Fetch the FULL filtered dataset from the DataTables endpoint (length = -1 so
    // the server returns every matching row, not just the current page). Mirrors the
    // filters applied in preXhr.dt so the export respects the on-screen filters.
    function meeFetchAllFilteredRows(onSuccess, onError) {
        var params = table.ajax.params();
        params.start = 0;
        params.length = -1; // Yajra: -1 disables pagination and returns all rows.
        params.filter = $('#filter_status').val() || 'active';
        params.course_filter = $('#course_filter').val();
        params.year_filter = $('#year_filter').val();
        params.duty_type_filter = $('#duty_type_filter').val();
        params.time_from_filter = $('#time_from_filter').val();
        params.time_to_filter = $('#time_to_filter').val();
        params.from_date_filter = $('#from_date_filter').val();
        params.to_date_filter = $('#to_date_filter').val();

        $.ajax({
            url: table.ajax.url(),
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(res) {
                onSuccess((res && res.data) ? res.data : []);
            },
            error: function() {
                if (typeof onError === 'function') {
                    onError();
                }
            }
        });
    }

    $('#printDownloadBtn').on('click', function() {
        // Open the window synchronously (inside the click gesture) so it isn't blocked,
        // then fill it once the full filtered dataset has been fetched.
        var printWindow = window.open('', '_blank');
        if (printWindow) {
            printWindow.document.write('<!DOCTYPE html><html><head><title>MDO/Escort Exemption</title></head>' +
                '<body style="font-family:Arial,sans-serif;margin:20px;">Preparing print…</body></html>');
        }

        var $btn = $(this).prop('disabled', true);

        meeFetchAllFilteredRows(function(rows) {
            $btn.prop('disabled', false);
            if (!printWindow) {
                return;
            }

            var columns = meeExportColumns();
            var head = '<tr>' + columns.map(function(c) {
                return '<th>' + $('<div>').text(c.title).html() + '</th>';
            }).join('') + '</tr>';

            var body = rows.map(function(row) {
                return '<tr>' + columns.map(function(c) {
                    return '<td>' + $('<div>').text(meeExportCellText(row[c.data])).html() + '</td>';
                }).join('') + '</tr>';
            }).join('');

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
            tableHtml += '<table><thead>' + head + '</thead><tbody>' + body + '</tbody></table>';
            tableHtml += '</body></html>';

            printWindow.document.open();
            printWindow.document.write(tableHtml);
            printWindow.document.close();

            setTimeout(function() {
                printWindow.print();
            }, 250);
        }, function() {
            $btn.prop('disabled', false);
            if (printWindow) {
                printWindow.close();
            }
            alert('Unable to prepare the print view. Please try again.');
        });
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
        var meeOtherDutyTypeId = null;

        $('#mdo_duty_type_master_pk option').each(function() {
            var optText = $(this).text().trim().toLowerCase();
            if (optText === 'escort') {
                meeEscortDutyTypeId = $(this).val();
            }
            if (optText === 'other') {
                meeOtherDutyTypeId = $(this).val();
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
                    duty_other: '#duty_other',
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

        function initFacultySelect2() {
            var $faculty = $('#faculty_master_pk');
            if ($faculty.hasClass('select2-hidden-accessible')) {
                return;
            }
            $faculty.select2({
                placeholder: 'Search Faculty (one or more)',
                width: '100%',
                closeOnSelect: false,
                dropdownParent: $('#meeAddModal')
            });
            // Keep native validation styling in sync with the search box.
            $faculty.on('change', function() {
                var val = $(this).val();
                if (val && val.length) {
                    $(this).removeClass('is-invalid');
                    $('#meeErrorFaculty').addClass('d-none');
                }
            });
        }

        function toggleFacultyField() {
            var dutyType = $('#mdo_duty_type_master_pk').val();
            if (meeEscortDutyTypeId && dutyType === meeEscortDutyTypeId) {
                $('#faculty_field_container').removeClass('d-none');
                initFacultySelect2();
                $('#faculty_master_pk').prop('required', true);
            } else {
                $('#faculty_field_container').addClass('d-none');
                $('#faculty_master_pk').val(null).prop('required', false);
                if ($('#faculty_master_pk').hasClass('select2-hidden-accessible')) {
                    $('#faculty_master_pk').trigger('change.select2');
                }
            }
        }

        function toggleDutyOtherField() {
            var dutyType = $('#mdo_duty_type_master_pk').val();
            if (meeOtherDutyTypeId && dutyType === meeOtherDutyTypeId) {
                $('#duty_other_container').removeClass('d-none');
                $('#duty_other').prop('required', true);
            } else {
                $('#duty_other_container').addClass('d-none');
                $('#duty_other').val('').prop('required', false);
                $('#meeErrorDutyOther').addClass('d-none');
                $('#duty_other').removeClass('is-invalid');
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
                    '<button type="button" class="btn-close btn-close-white ms-1" aria-label="Remove ' + escapeHtml(student.display_name) + '"></button>' +
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
                    '<button type="button" class="btn-close btn-close-white ms-1" aria-label="Remove ' + escapeHtml(student.display_name) + '"></button>' +
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
                    selectedDate: selectedDate,
                    selectedTimeFrom: $('#Time_from').val(),
                    selectedTimeTo: $('#Time_to').val()
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
            // form.reset() changes the native selects only; repaint Select2.
            $('#meeCourseDropdown, #mdo_duty_type_master_pk').trigger('change.select2');
            meeAllStudents = [];
            meeAssignedStudents = [];
            meePickerSelectedIds.clear();
            meePickerStudentsMap = {};
            $('#meeStudentListSearch').val('');
            $('#hiddenStudentSelect').empty();
            $('#meeRecordPk').val('');
            $('#meeEditStudentName, #meeEditCourseName').text('—');
            toggleFacultyField();
            toggleDutyOtherField();
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
            if (!meeCheckDate($('#mdo_date'), $('#meeErrorDate'))) {
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
            var facultyVal = $('#faculty_master_pk').val();
            if (meeEscortDutyTypeId && $('#mdo_duty_type_master_pk').val() === meeEscortDutyTypeId && (!facultyVal || !facultyVal.length)) {
                $('#meeErrorFaculty').removeClass('d-none');
                $('#faculty_master_pk').addClass('is-invalid');
                valid = false;
            }
            if (meeOtherDutyTypeId && $('#mdo_duty_type_master_pk').val() === meeOtherDutyTypeId && !$('#duty_other').val().trim()) {
                $('#meeErrorDutyOther').removeClass('d-none');
                $('#duty_other').addClass('is-invalid');
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

        $('#mdo_duty_type_master_pk').on('change', function() {
            toggleFacultyField();
            toggleDutyOtherField();
        });

        // Course, date or time change invalidates the previously picked students,
        // since the available list depends on all of them (time-slot conflict check).
        $('#meeCourseDropdown, #mdo_date, #Time_from, #Time_to').on('change', function() {
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
            if (!meeCheckDate($('#mdo_date'), $('#meeErrorDate'))) {
                $('#mdo_date').focus();
                return;
            }
            // Time is required so the picker can exclude students already busy in
            // that slot. Without it we cannot run the time-conflict check.
            if (!$('#Time_from').val()) {
                $('#meeErrorTimeFrom').removeClass('d-none');
                $('#Time_from').addClass('is-invalid').focus();
                return;
            }
            if (!$('#Time_to').val()) {
                $('#meeErrorTimeTo').removeClass('d-none');
                $('#Time_to').addClass('is-invalid').focus();
                return;
            }
            if ($('#Time_to').val() <= $('#Time_from').val()) {
                $('#meeErrorTimeTo').removeClass('d-none').text('End time must be after start time.');
                $('#Time_to').addClass('is-invalid').focus();
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

        // Faculty is a multi-select (one or more) for Escort duty.
        $('#meeEditFaculty').select2({
            placeholder: 'Search Faculty (one or more)',
            width: '100%',
            closeOnSelect: false,
            dropdownParent: $('#meeEditModal')
        });
        $('#meeEditFaculty').on('change', function() {
            var val = $(this).val();
            if (val && val.length) {
                $(this).removeClass('is-invalid');
                $('#meeEditErrorFaculty').addClass('d-none');
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
                $('#meeEditFaculty').val(null).trigger('change').prop('required', false);
            }
        }

        function validateEditForm() {
            clearEditErrors();
            var valid = true;
            if (!$('#meeEditDutyType').val()) { $('#meeEditErrorDutyType').removeClass('d-none'); $('#meeEditDutyType').addClass('is-invalid'); valid = false; }
            if (!meeCheckDate($('#meeEditDate'), $('#meeEditErrorDate'))) { valid = false; }
            if (!$('#meeEditTimeFrom').val()) { $('#meeEditErrorTimeFrom').removeClass('d-none'); $('#meeEditTimeFrom').addClass('is-invalid'); valid = false; }
            if (!$('#meeEditTimeTo').val()) { $('#meeEditErrorTimeTo').removeClass('d-none'); $('#meeEditTimeTo').addClass('is-invalid'); valid = false; }
            if ($('#meeEditTimeFrom').val() && $('#meeEditTimeTo').val() && $('#meeEditTimeTo').val() <= $('#meeEditTimeFrom').val()) {
                $('#meeEditErrorTimeTo').removeClass('d-none').text('End time must be after start time.');
                $('#meeEditTimeTo').addClass('is-invalid'); valid = false;
            }
            var editFacultyVal = $('#meeEditFaculty').val();
            if (editEscortDutyTypeId && $('#meeEditDutyType').val() === editEscortDutyTypeId && (!editFacultyVal || !editFacultyVal.length)) {
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
                    $('#meeEditDutyType').val(record.mdo_duty_type_master_pk).trigger('change.select2');
                    $('#meeEditDate').val(record.mdo_date || '');
                    $('#meeEditTimeFrom').val(record.Time_from || '');
                    $('#meeEditTimeTo').val(record.Time_to || '');
                    var facultyPks = (record.faculty_master_pks || []).map(String);
                    $('#meeEditFaculty').val(facultyPks).trigger('change');
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

        // Faculty is a multi-select (one or more) for Escort duty.
        $('#meeBulkFaculty').select2({
            placeholder: 'Search Faculty (one or more)',
            width: '100%',
            closeOnSelect: false,
            dropdownParent: $('#meeBulkUploadModal')
        });
        $('#meeBulkFaculty').on('change', function() {
            var val = $(this).val();
            if (val && val.length) {
                $(this).removeClass('is-invalid');
                $('#meeBulkErrorFaculty').addClass('d-none');
            }
        });

        function toggleBulkFaculty() {
            var dutyType = $('#meeBulkDutyType').val();
            if (bulkEscortDutyTypeId && dutyType === bulkEscortDutyTypeId) {
                $('#meeBulkFacultyContainer').removeClass('d-none');
                $('#meeBulkFaculty').prop('required', true);
            } else {
                $('#meeBulkFacultyContainer').addClass('d-none');
                $('#meeBulkFaculty').val(null).trigger('change').prop('required', false);
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
            $('#meeBulkCourse, #meeBulkDutyType').trigger('change.select2');
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
            var bulkFacultyVal = $('#meeBulkFaculty').val();
            if (bulkEscortDutyTypeId && $('#meeBulkDutyType').val() === bulkEscortDutyTypeId && (!bulkFacultyVal || !bulkFacultyVal.length)) {
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
                // Render at most 100 rows so a huge error list can't flood the modal
                // (which pushed the Upload button out of view). The rest are summarised.
                var MAX_SHOWN = 100;
                errors.slice(0, MAX_SHOWN).forEach(function(err) {
                    $list.append($('<li>').text(err));
                });
                if (errors.length > MAX_SHOWN) {
                    $list.append(
                        $('<li>').addClass('fw-semibold list-unstyled mt-1')
                            .text('…and ' + (errors.length - MAX_SHOWN) + ' more row(s) skipped.')
                    );
                }
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
                // 2 minute cap so a hung/slow request can never leave the button stuck.
                timeout: 120000,
                success: function(response) {
                    // Wrapped so a render error can't stop `complete` re-enabling the button.
                    try {
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
                    } catch (err) {
                        console.error('Bulk upload success handler error:', err);
                    }
                },
                error: function(xhr, textStatus) {
                    try {
                        var response = xhr.responseJSON || {};
                        var message = response.message
                            || (textStatus === 'timeout'
                                ? 'The upload took too long and was stopped. Please try a smaller file.'
                                : 'Something went wrong. Please try again.');
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
                    } catch (err) {
                        console.error('Bulk upload error handler error:', err);
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
        var $btn = $(this).prop('disabled', true);

        meeFetchAllFilteredRows(function(data) {
            $btn.prop('disabled', false);

            var columns = meeExportColumns();

            function csvCell(text) {
                return '"' + String(text).replace(/"/g, '""') + '"';
            }

            var rows = [];
            // Header row
            rows.push(columns.map(function(c) { return csvCell(c.title); }).join(','));
            // Data rows (full filtered dataset)
            data.forEach(function(row) {
                rows.push(columns.map(function(c) {
                    return csvCell(meeExportCellText(row[c.data]));
                }).join(','));
            });

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
        }, function() {
            $btn.prop('disabled', false);
            alert('Unable to download the data. Please try again.');
        });
    });
});
</script>
@endpush
