@extends('admin.layouts.master')

@section('title', 'Create Nomination Drive')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
{{-- Shares the .cs-master-page CSS scope with the rest of the Club/ Society
     module (icon-over-label actions), defined once in public/css/custom.css. --}}
<div class="container-fluid cs-master-page nd-page">
    <x-breadcrum title="Create Nomination Drive">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="ndAddBtn" data-bs-toggle="modal" data-bs-target="#ndFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Create Nomination Drive</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Active / Archived follows the COURSE's own status, the same split Course
         Master and Attendance use. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter drives by course status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        id="ndFilterActive" data-nd-status="active" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="ndFilterArchive" data-nd-status="archive" aria-pressed="false">Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('master.nomination.drive.export') }}" id="ndDownloadBtn"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </a>
            <a href="{{ route('master.nomination.drive.print') }}" id="ndPrintBtn"
               target="_blank" rel="noopener" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="ndCourseFilterSelect" class="cs-select2 form-select" aria-label="Filter by course name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="ndRemoveFilter">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="ndBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#ndColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="ndDtSearch" class="programme-dt-search" data-dt-search-for="nominationdrive-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table text-normal']) !!}
                </div>
                <div id="ndDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="nominationdrive-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Create / Edit Nomination Drive Modal -->
<div class="modal fade" id="ndFormModal" tabindex="-1" aria-labelledby="ndFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="ndDriveForm" action="{{ route('master.nomination.drive.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="ndPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="ndFormModalLabel">Create Nomination Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="ndFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="ndDriveName" class="form-label fw-semibold">Drive Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ndDriveName" name="drive_name"
                                   placeholder="eg. House Of Academy FC-97" maxlength="255" autocomplete="off" required>
                            <div class="invalid-feedback" data-field="drive_name"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="ndCourse" class="form-label fw-semibold">Course Name <span class="text-danger">*</span></label>
                            <select class="cs-select2 form-select" id="ndCourse" name="course_master_pk" required>
                                <option value="">Select Course</option>
                                <optgroup label="Active" id="ndCourseActiveGroup">
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                                    @endforeach
                                </optgroup>
                                @if (count($archiveCourses))
                                    <optgroup label="Archived">
                                        @foreach ($archiveCourses as $course)
                                            <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <div class="invalid-feedback" data-field="course_master_pk"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="ndStartDate" class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="ndStartDate" name="start_date" required>
                            <div class="invalid-feedback" data-field="start_date"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="ndEndDate" class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="ndEndDate" name="end_date" required>
                            <div class="invalid-feedback" data-field="end_date"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <span class="form-label fw-semibold d-block">Self Nomination Allow</span>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="self_nomination_allow" value="1" id="ndSelfYes" checked>
                                    <label class="form-check-label" for="ndSelfYes">Yes</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="self_nomination_allow" value="0" id="ndSelfNo">
                                    <label class="form-check-label" for="ndSelfNo">No</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <span class="form-label fw-semibold d-block">Mark Same Date for All</span>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="same_date_for_all" value="1" id="ndSameYes" checked>
                                    <label class="form-check-label" for="ndSameYes">Yes</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="same_date_for_all" value="0" id="ndSameNo">
                                    <label class="form-check-label" for="ndSameNo">No</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="invalid-feedback d-block mb-2" data-field="society_pks"></div>

                    <div class="table-responsive nd-society-table-wrap">
                        <table class="table align-middle mb-0 nd-society-table">
                            <thead>
                                <tr>
                                    <th style="width:3rem;" class="text-center">
                                        <input type="checkbox" class="form-check-input m-0" id="ndToggleAll" aria-label="Select all societies">
                                    </th>
                                    <th style="width:16rem;">Society Name</th>
                                    <th style="width:11rem;" class="nd-date-col">Start Date</th>
                                    <th style="width:11rem;" class="nd-date-col">End Date</th>
                                    <th>Post</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clubSocieties as $club)
                                    <tr data-society-row="{{ $club->pk }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input m-0 nd-society-checkbox"
                                                   id="ndSociety{{ $club->pk }}" name="society_pks[]" value="{{ $club->pk }}"
                                                   aria-label="Include {{ $club->club_society_name }}">
                                        </td>
                                        <td>
                                            <label class="mb-0" for="ndSociety{{ $club->pk }}">{{ $club->club_society_name }}</label>
                                        </td>
                                        <td class="nd-date-col">
                                            <input type="date" class="form-control nd-society-start"
                                                   name="society_start_date[{{ $club->pk }}]"
                                                   aria-label="Start date for {{ $club->club_society_name }}" disabled>
                                            <div class="invalid-feedback" data-field="society_start_date.{{ $club->pk }}"></div>
                                        </td>
                                        <td class="nd-date-col">
                                            <input type="date" class="form-control nd-society-end"
                                                   name="society_end_date[{{ $club->pk }}]"
                                                   aria-label="End date for {{ $club->club_society_name }}" disabled>
                                            <div class="invalid-feedback" data-field="society_end_date.{{ $club->pk }}"></div>
                                        </td>
                                        <td>
                                            <div class="nd-post-grid">
                                                @foreach ($roles as $role)
                                                    <label class="d-flex align-items-center gap-2 mb-0 nd-post-option"
                                                           for="ndPost{{ $club->pk }}_{{ $role->pk }}">
                                                        <input type="checkbox" class="form-check-input m-0 nd-post-checkbox"
                                                               id="ndPost{{ $club->pk }}_{{ $role->pk }}"
                                                               name="society_posts[{{ $club->pk }}][]" value="{{ $role->pk }}" disabled>
                                                        <span>{{ $role->club_society_role_name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="invalid-feedback" data-field="society_posts.{{ $club->pk }}"></div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-body-secondary py-4">
                                            No clubs/ societies defined yet — add them under Define Club/ Society first.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-danger rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="ndSubmitBtn">Create Nomination Drive</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="ndColumnVisibilityModal" tabindex="-1" aria-labelledby="ndColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="ndColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="ndColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@include('admin.master.partials.club_society_select2')
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    /* Read by the DataTable's ajax data callback so the pill + course filter
       travel with every draw, paging included. */
    var ndStatus = 'active';
    var ndCourse = '';
    window.ndStatusFilter = function () { return ndStatus; };
    window.ndCourseFilter = function () { return ndCourse; };

    $(document).ready(function () {
        var TABLE_ID = '#nominationdrive-table';

        var COURSES = {
            active:  @json($courses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->couse_short_name ?: $c->course_name])->values()),
            archive: @json($archiveCourses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->couse_short_name ?: $c->course_name])->values())
        };

        function dt() {
            return $.fn.DataTable.isDataTable(TABLE_ID) ? $(TABLE_ID).DataTable() : null;
        }
        function reload() {
            var t = dt();
            if (t) { t.ajax.reload(null, false); }
        }

        /* ---- Active / Archived pills ---- */
        $('.programme-status-pill[data-nd-status]').on('click', function () {
            var $btn = $(this);
            if ($btn.hasClass('active')) { return; }

            $('.programme-status-pill[data-nd-status]')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $btn.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

            ndStatus = $btn.data('nd-status');

            var $sel = $('#ndCourseFilterSelect');
            $sel.find('option:not(:first)').remove();
            (COURSES[ndStatus] || []).forEach(function (c) {
                $sel.append($('<option></option>').attr('value', c.pk).text(c.name));
            });
            ndCourse = '';
            $sel.val('').trigger('change.select2');

            syncExportLinks();
            var t = dt(); if (t) { t.ajax.reload(null, true); }
        });

        /* ---- Course filter + Remove Filter ---- */
        $('#ndCourseFilterSelect').on('change', function () {
            ndCourse = $(this).val() || '';
            syncExportLinks();
            var t = dt(); if (t) { t.ajax.reload(null, true); }
        });

        $('#ndRemoveFilter').on('click', function () {
            ndCourse = '';
            $('#ndCourseFilterSelect').val('').trigger('change.select2');
            var t = dt();
            if (t) { t.search(''); t.ajax.reload(null, true); }
            syncExportLinks();
        });

        function syncExportLinks() {
            var qs = '?status_filter=' + encodeURIComponent(ndStatus)
                   + (ndCourse ? '&course_master_pk=' + encodeURIComponent(ndCourse) : '');
            $('#ndDownloadBtn').attr('href', "{{ route('master.nomination.drive.export') }}" + qs);
            $('#ndPrintBtn').attr('href', "{{ route('master.nomination.drive.print') }}" + qs);
        }
        syncExportLinks();

        /* ---- Column show / hide ---- */
        var ndColStorageKey = 'ndGrid:hiddenColumns:v1';
        function ndGetHiddenCols() {
            try {
                var raw = localStorage.getItem(ndColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }
        function ndPersistHiddenCols(arr) {
            try { localStorage.setItem(ndColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }
        function setupNdColumns(table) {
            if (!table) { return; }
            var hidden = ndGetHiddenCols();
            table.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            table.columns.adjust();

            var $grid = $('#ndColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            table.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }

                var inputId = 'ndcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = ndGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); }
                    else { if (pos === -1) h.push(idx); }
                    ndPersistHiddenCols(h);
                    table.column(idx).visible(this.checked, false);
                    table.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }
        setTimeout(function () { setupNdColumns(dt()); }, 150);

        /* ---- Society row enable/disable ----
           A society's dates and posts only apply once it is ticked; a disabled
           input is also not submitted, which keeps the payload to ticked rows. */
        function sameDatesForAll() {
            return $('input[name="same_date_for_all"]:checked').val() === '1';
        }

        function syncSocietyRow($row) {
            var on = $row.find('.nd-society-checkbox').is(':checked');
            $row.toggleClass('nd-row-on', on);
            $row.find('.nd-post-checkbox').prop('disabled', !on);
            // Per-society dates are only in play when dates are NOT shared.
            var datesOn = on && !sameDatesForAll();
            $row.find('.nd-society-start, .nd-society-end').prop('disabled', !datesOn);
            if (!datesOn) { $row.find('.nd-society-start, .nd-society-end').val(''); }
        }

        function syncAllSocietyRows() {
            $('#ndDriveForm tbody tr[data-society-row]').each(function () { syncSocietyRow($(this)); });
            // Hide the per-society date columns entirely when dates are shared.
            $('.nd-date-col').toggleClass('d-none', sameDatesForAll());
        }

        $(document).on('change', '.nd-society-checkbox', function () {
            syncSocietyRow($(this).closest('tr'));
            var total = $('.nd-society-checkbox').length;
            var on = $('.nd-society-checkbox:checked').length;
            $('#ndToggleAll').prop('checked', total > 0 && on === total)
                             .prop('indeterminate', on > 0 && on < total);
        });

        $(document).on('change', 'input[name="same_date_for_all"]', syncAllSocietyRows);

        $('#ndToggleAll').on('change', function () {
            var on = this.checked;
            $('.nd-society-checkbox').prop('checked', on);
            syncAllSocietyRows();
            $(this).prop('indeterminate', false);
        });

        /* ---- Create / Edit modal ---- */
        var $form  = $('#ndDriveForm');
        var $alert = $('#ndFormAlert');

        function ndClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function ndResetForm() {
            $form[0].reset();
            $('#ndPk').val('');
            $('.nd-society-checkbox, .nd-post-checkbox').prop('checked', false);
            $('#ndToggleAll').prop('checked', false).prop('indeterminate', false);
            $('.nd-society-start, .nd-society-end').val('');
            $('input[name="self_nomination_allow"][value="1"]').prop('checked', true);
            $('input[name="same_date_for_all"][value="1"]').prop('checked', true);
            syncAllSocietyRows();
            ndClearErrors();
            // Native reset/val() do not reach Select2 — redraw it.
            $form.find('select.cs-select2').trigger('change.select2');
        }

        function ndSetMode(isEdit) {
            $('#ndFormModalLabel').text(isEdit ? 'Edit Nomination Drive' : 'Create Nomination Drive');
            $('#ndSubmitBtn').text(isEdit ? 'Update Nomination Drive' : 'Create Nomination Drive');
        }

        $('#ndAddBtn').on('click', function () {
            ndResetForm();
            ndSetMode(false);
        });

        var editUrlTemplate = "{{ route('master.nomination.drive.edit', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .nd-edit-btn', function () {
            var id = $(this).data('id');
            ndResetForm();
            ndSetMode(true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('ndFormModal')).show();

            $.ajax({
                url: editUrlTemplate.replace('__ID__', encodeURIComponent(id)),
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $('#ndPk').val(res.pk);
                    $('#ndDriveName').val(res.drive_name);
                    $('#ndCourse').val(String(res.course_master_pk)).trigger('change.select2');
                    $('#ndStartDate').val(res.start_date);
                    $('#ndEndDate').val(res.end_date);
                    $('input[name="self_nomination_allow"][value="' + (res.self_nomination_allow ? '1' : '0') + '"]').prop('checked', true);
                    $('input[name="same_date_for_all"][value="' + (res.same_date_for_all ? '1' : '0') + '"]').prop('checked', true);

                    (res.societies || []).forEach(function (s) {
                        var $tr = $('tr[data-society-row="' + s.club_society_master_pk + '"]');
                        if (!$tr.length) { return; }
                        $tr.find('.nd-society-checkbox').prop('checked', true);
                        (s.post_pks || []).forEach(function (rp) {
                            $tr.find('.nd-post-checkbox[value="' + rp + '"]').prop('checked', true);
                        });
                        if (s.start_date) { $tr.find('.nd-society-start').val(s.start_date); }
                        if (s.end_date) { $tr.find('.nd-society-end').val(s.end_date); }
                    });

                    syncAllSocietyRows();
                    // Re-apply dates: syncAllSocietyRows clears them when shared.
                    (res.societies || []).forEach(function (s) {
                        var $tr = $('tr[data-society-row="' + s.club_society_master_pk + '"]');
                        if (s.start_date) { $tr.find('.nd-society-start').val(s.start_date); }
                        if (s.end_date) { $tr.find('.nd-society-end').val(s.end_date); }
                    });

                    var total = $('.nd-society-checkbox').length;
                    var on = $('.nd-society-checkbox:checked').length;
                    $('#ndToggleAll').prop('checked', total > 0 && on === total)
                                     .prop('indeterminate', on > 0 && on < total);
                },
                error: function () {
                    $alert.removeClass('d-none alert-success').addClass('alert-danger')
                          .text('Could not load this drive. Please close and try again.');
                }
            });
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            ndClearErrors();

            if (!$('.nd-society-checkbox:checked').length) {
                $form.find('.invalid-feedback[data-field="society_pks"]').text('Select at least one society.');
                return;
            }

            var $submit = $('#ndSubmitBtn');
            var originalText = $submit.text();
            $submit.prop('disabled', true)
                   .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (response) {
                    $alert.removeClass('d-none alert-danger').addClass('alert-success')
                          .html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));
                    reload();
                    setTimeout(function () {
                        var instance = bootstrap.Modal.getInstance(document.getElementById('ndFormModal'));
                        if (instance) { instance.hide(); }
                        ndResetForm();
                    }, 900);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            var $target = $form.find('.invalid-feedback[data-field="' + field + '"]');
                            if ($target.length) {
                                $target.text(errors[field][0]);
                                $target.closest('td, .col-12, .col-md-6').find('input, select').first().addClass('is-invalid');
                            } else {
                                var key = field.replace(/\.\d+$/, '').replace(/\.\*$/, '');
                                $form.find('[name="' + key + '"]').addClass('is-invalid');
                                $form.find('.invalid-feedback[data-field="' + key + '"]').text(errors[field][0]);
                            }
                        });
                        $alert.removeClass('d-none alert-success').addClass('alert-danger')
                              .text(xhr.responseJSON.message || 'Please correct the highlighted fields.');
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

        document.getElementById('ndFormModal').addEventListener('hidden.bs.modal', function () {
            ndResetForm();
            ndSetMode(false);
        });

        /* ---- Status toggles (accept / withdraw are independent) ---- */
        var toggleUrlTemplate = "{{ route('master.nomination.drive.toggle', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .nd-toggle-btn', function () {
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: toggleUrlTemplate.replace('__ID__', encodeURIComponent($btn.data('id'))),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    field: $btn.data('field'),
                    value: $btn.data('next')
                },
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: (res && res.message) || 'Status updated.'
                    });
                    reload();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message : 'Could not update the status.';
                    Swal.fire('Error!', msg, 'error');
                    $btn.prop('disabled', false);
                }
            });
        });

        /* ---- Delete ---- */
        var deleteUrlTemplate = "{{ route('master.nomination.drive.destroy', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .nd-delete-btn', function () {
            var $btn = $(this);
            var name = $btn.data('label') || 'this drive';

            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete "' + name + '" and every nomination recorded against it? This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                $btn.prop('disabled', true);

                $.ajax({
                    url: deleteUrlTemplate.replace('__ID__', encodeURIComponent($btn.data('id'))),
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        Swal.fire('Deleted!', (res && res.message) || 'Nomination drive deleted.', 'success');
                        reload();
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message : 'Something went wrong while deleting.';
                        Swal.fire('Error!', msg, 'error');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });

        // Initial row state.
        syncAllSocietyRows();
    });
</script>
@endpush
