@extends('admin.layouts.master')

@section('title', 'Create Election Drive')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
{{-- Shares the .cs-master-page CSS scope with the rest of the Club/ Society
     module (icon-over-label actions), defined once in public/css/custom.css. --}}
<div class="container-fluid cs-master-page ed-page">
    <x-breadcrum title="Create Election Drive">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="edAddBtn" data-bs-toggle="modal" data-bs-target="#edFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Create Election Drive</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Active / Archived follows the COURSE's own status (reached through the
         nomination drive), the same split Course Master and Attendance use. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter drives by course status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        id="edFilterActive" data-ed-status="active" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="edFilterArchive" data-ed-status="archive" aria-pressed="false">Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('master.election.drive.export') }}" id="edDownloadBtn"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </a>
            <a href="{{ route('master.election.drive.print') }}" id="edPrintBtn"
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
                        <select id="edCourseFilterSelect" class="cs-select2 form-select" aria-label="Filter by course name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="edRemoveFilter">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="edBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#edColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="edDtSearch" class="programme-dt-search" data-dt-search-for="electiondrive-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="edDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="electiondrive-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Create / Edit Election Drive Modal -->
<div class="modal fade" id="edFormModal" tabindex="-1" aria-labelledby="edFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="edDriveForm" action="{{ route('master.election.drive.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="pk" id="edPk" value="">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="edFormModalLabel">Create Election Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="edDriveName" class="form-label fw-semibold">Election Drive Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edDriveName" name="election_drive_name"
                                   placeholder="eg. House Of Academy FC-97" maxlength="255" autocomplete="off" required>
                            <div class="invalid-feedback" data-field="election_drive_name"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edNominationDrive" class="form-label fw-semibold">Nomination Drive <span class="text-danger">*</span></label>
                            <select class="cs-select2 form-select" id="edNominationDrive" name="nomination_drive_pk" required>
                                <option value="">Select Nomination Drive</option>
                                @foreach ($nominationDrives as $nd)
                                    <option value="{{ $nd->pk }}" data-token="{{ encrypt($nd->pk) }}">
                                        {{ $nd->drive_name }} — {{ $nd->course_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="nomination_drive_pk"></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="edDate" class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edDate" name="drive_date" required>
                            <div class="invalid-feedback" data-field="drive_date"></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edStartTime" class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="edStartTime" name="start_time" required>
                            <div class="invalid-feedback" data-field="start_time"></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edEndTime" class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="edEndTime" name="end_time" required>
                            <div class="invalid-feedback" data-field="end_time"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <span class="form-label fw-semibold d-block">Mark Same Date for All</span>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="same_date_for_all" value="1" id="edSameDateYes" checked>
                                    <label class="form-check-label" for="edSameDateYes">Yes</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="same_date_for_all" value="0" id="edSameDateNo">
                                    <label class="form-check-label" for="edSameDateNo">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <span class="form-label fw-semibold d-block">Mark Same Time for All</span>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="same_time_for_all" value="1" id="edSameTimeYes" checked>
                                    <label class="form-check-label" for="edSameTimeYes">Yes</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="same_time_for_all" value="0" id="edSameTimeNo">
                                    <label class="form-check-label" for="edSameTimeNo">No</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="invalid-feedback d-block mb-2" data-field="society_pks"></div>

                    <div class="table-responsive ed-society-table-wrap">
                        <table class="table align-middle mb-0 ed-society-table">
                            <thead>
                                <tr>
                                    <th style="width:3rem;" class="text-center">
                                        <input type="checkbox" class="form-check-input m-0" id="edToggleAll" aria-label="Select all societies">
                                    </th>
                                    <th style="width:14rem;">Society Name</th>
                                    <th style="width:13rem;">Number of Post</th>
                                    <th style="width:11rem;" class="ed-date-col">Start Date</th>
                                    <th class="ed-time-col">Time</th>
                                </tr>
                            </thead>
                            <tbody id="edSocietyBody">
                                <tr id="edSocietyPlaceholder">
                                    <td colspan="5" class="text-center text-body-secondary py-4">
                                        Select a nomination drive to load its societies.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-danger rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="edSubmitBtn">Create Election Drive</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="edColumnVisibilityModal" tabindex="-1" aria-labelledby="edColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="edColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="edColumnToggleGrid"></div>
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
    var edStatus = 'active';
    var edCourse = '';
    window.edStatusFilter = function () { return edStatus; };
    window.edCourseFilter = function () { return edCourse; };

    $(document).ready(function () {
        var TABLE_ID = '#electiondrive-table';

        var COURSES = {
            active:  @json($courses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->couse_short_name ?: $c->course_name])->values()),
            archive: @json($archiveCourses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->couse_short_name ?: $c->course_name])->values())
        };

        function dt() {
            return $.fn.DataTable.isDataTable(TABLE_ID) ? $(TABLE_ID).DataTable() : null;
        }
        function reload(reset) {
            var t = dt();
            if (t) { t.ajax.reload(null, reset === true); }
        }

        /* ---- Active / Archived pills ---- */
        $('.programme-status-pill[data-ed-status]').on('click', function () {
            var $btn = $(this);
            if ($btn.hasClass('active')) { return; }

            $('.programme-status-pill[data-ed-status]')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $btn.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

            edStatus = $btn.data('ed-status');

            var $sel = $('#edCourseFilterSelect');
            $sel.find('option:not(:first)').remove();
            (COURSES[edStatus] || []).forEach(function (c) {
                $sel.append($('<option></option>').attr('value', c.pk).text(c.name));
            });
            edCourse = '';
            $sel.val('').trigger('change.select2');

            syncExportLinks();
            reload(true);
        });

        /* ---- Course filter + Remove Filter ---- */
        $('#edCourseFilterSelect').on('change', function () {
            edCourse = $(this).val() || '';
            syncExportLinks();
            reload(true);
        });

        $('#edRemoveFilter').on('click', function () {
            edCourse = '';
            $('#edCourseFilterSelect').val('').trigger('change.select2');
            var t = dt();
            if (t) { t.search(''); }
            syncExportLinks();
            reload(true);
        });

        function syncExportLinks() {
            var qs = '?status_filter=' + encodeURIComponent(edStatus)
                   + (edCourse ? '&course_master_pk=' + encodeURIComponent(edCourse) : '');
            $('#edDownloadBtn').attr('href', "{{ route('master.election.drive.export') }}" + qs);
            $('#edPrintBtn').attr('href', "{{ route('master.election.drive.print') }}" + qs);
        }
        syncExportLinks();

        /* ---- Column show / hide ---- */
        var edColKey = 'edGrid:hiddenColumns:v1';
        function edHidden() {
            try {
                var raw = localStorage.getItem(edColKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }
        function setupEdColumns(table) {
            if (!table) { return; }
            var hidden = edHidden();
            table.columns().every(function () {
                this.visible(hidden.indexOf(this.index()) === -1, false);
            });
            table.columns.adjust();

            var $grid = $('#edColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            table.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                var inputId = 'edcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
                $cb.on('change', function () {
                    var h = edHidden();
                    var pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); }
                    else { if (pos === -1) h.push(idx); }
                    try { localStorage.setItem(edColKey, JSON.stringify(h)); } catch (e) {}
                    table.column(idx).visible(this.checked, false);
                    table.columns.adjust();
                });
                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }
        setTimeout(function () { setupEdColumns(dt()); }, 150);

        /* ---- Society table: driven by the chosen nomination drive ---- */
        var societiesUrlTemplate = "{{ route('master.election.drive.societies', ['id' => '__ID__']) }}";
        var pendingPrefill = null;   // societies to re-tick after an Edit load

        function sameDate() { return $('input[name="same_date_for_all"]:checked').val() === '1'; }
        function sameTime() { return $('input[name="same_time_for_all"]:checked').val() === '1'; }

        function renderPostSummary(posts) {
            if (!posts || !posts.length) {
                return $('<span class="text-body-secondary small">No posts</span>');
            }
            var $box = $('<div class="ed-post-summary"></div>');
            posts.forEach(function (p) {
                // "<accepted nominees> / <posts available>"
                $box.append(
                    $('<div class="ed-post-line"></div>')
                        .append($('<span></span>').text(p.role_name + ' = '))
                        .append($('<strong></strong>').text(p.accepted + '/' + p.seats))
                );
            });
            return $box;
        }

        function buildSocietyRow(s) {
            var $tr = $('<tr></tr>').attr('data-society-row', s.club_society_master_pk);

            $tr.append($('<td class="text-center"></td>').append(
                $('<input type="checkbox" class="form-check-input m-0 ed-society-checkbox">')
                    .attr({ id: 'edSociety' + s.club_society_master_pk, name: 'society_pks[]', value: s.club_society_master_pk,
                            'aria-label': 'Include ' + s.club_society_name })
            ));

            $tr.append($('<td></td>').append(
                $('<label class="mb-0"></label>').attr('for', 'edSociety' + s.club_society_master_pk).text(s.club_society_name)
            ));

            $tr.append($('<td></td>').append(renderPostSummary(s.posts)));

            $tr.append(
                $('<td class="ed-date-col"></td>')
                    .append($('<input type="date" class="form-control ed-society-date" disabled>')
                        .attr({ name: 'society_date[' + s.club_society_master_pk + ']',
                                'aria-label': 'Date for ' + s.club_society_name }))
                    .append($('<div class="invalid-feedback"></div>').attr('data-field', 'society_date.' + s.club_society_master_pk))
            );

            $tr.append(
                $('<td class="ed-time-col"></td>').append(
                    $('<div class="d-flex align-items-start gap-2"></div>')
                        .append($('<div class="flex-fill"></div>')
                            .append($('<input type="time" class="form-control ed-society-start" disabled>')
                                .attr({ name: 'society_start_time[' + s.club_society_master_pk + ']',
                                        'aria-label': 'Start time for ' + s.club_society_name }))
                            .append($('<div class="invalid-feedback"></div>').attr('data-field', 'society_start_time.' + s.club_society_master_pk)))
                        .append($('<div class="flex-fill"></div>')
                            .append($('<input type="time" class="form-control ed-society-end" disabled>')
                                .attr({ name: 'society_end_time[' + s.club_society_master_pk + ']',
                                        'aria-label': 'End time for ' + s.club_society_name }))
                            .append($('<div class="invalid-feedback"></div>').attr('data-field', 'society_end_time.' + s.club_society_master_pk)))
                )
            );

            return $tr;
        }

        function loadSocieties(token, onDone) {
            var $body = $('#edSocietyBody');
            $body.html('<tr><td colspan="5" class="text-center text-body-secondary py-4">Loading societies…</td></tr>');

            if (!token) {
                $body.html('<tr id="edSocietyPlaceholder"><td colspan="5" class="text-center text-body-secondary py-4">Select a nomination drive to load its societies.</td></tr>');
                if (onDone) { onDone(); }
                return;
            }

            $.ajax({
                url: societiesUrlTemplate.replace('__ID__', encodeURIComponent(token)),
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $body.empty();
                    if (!res.societies || !res.societies.length) {
                        $body.html('<tr><td colspan="5" class="text-center text-body-secondary py-4">That nomination drive has no societies.</td></tr>');
                    } else {
                        res.societies.forEach(function (s) { $body.append(buildSocietyRow(s)); });
                    }
                    syncAllSocietyRows();
                    if (onDone) { onDone(); }
                },
                error: function () {
                    $body.html('<tr><td colspan="5" class="text-center text-danger py-4">Could not load societies.</td></tr>');
                    if (onDone) { onDone(); }
                }
            });
        }

        $('#edNominationDrive').on('change', function () {
            var token = $(this).find('option:selected').data('token') || '';
            pendingPrefill = null;
            loadSocieties(token);
        });

        function syncSocietyRow($row) {
            var on = $row.find('.ed-society-checkbox').is(':checked');
            $row.toggleClass('ed-row-on', on);

            var dateOn = on && !sameDate();
            var timeOn = on && !sameTime();
            $row.find('.ed-society-date').prop('disabled', !dateOn);
            $row.find('.ed-society-start, .ed-society-end').prop('disabled', !timeOn);
            if (!dateOn) { $row.find('.ed-society-date').val(''); }
            if (!timeOn) { $row.find('.ed-society-start, .ed-society-end').val(''); }
        }

        function syncAllSocietyRows() {
            $('#edSocietyBody tr[data-society-row]').each(function () { syncSocietyRow($(this)); });
            // Hide the per-society columns entirely when the drive shares them.
            $('.ed-date-col').toggleClass('d-none', sameDate());
            $('.ed-time-col').toggleClass('d-none', sameTime());
        }

        $(document).on('change', '.ed-society-checkbox', function () {
            syncSocietyRow($(this).closest('tr'));
            var total = $('.ed-society-checkbox').length;
            var on = $('.ed-society-checkbox:checked').length;
            $('#edToggleAll').prop('checked', total > 0 && on === total)
                             .prop('indeterminate', on > 0 && on < total);
        });

        $(document).on('change', 'input[name="same_date_for_all"], input[name="same_time_for_all"]', syncAllSocietyRows);

        $('#edToggleAll').on('change', function () {
            var on = this.checked;
            $('.ed-society-checkbox').prop('checked', on);
            syncAllSocietyRows();
            $(this).prop('indeterminate', false);
        });

        /* ---- Create / Edit modal ---- */
        var $form  = $('#edDriveForm');
        var $alert = $('#edFormAlert');

        function edClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function edResetForm() {
            $form[0].reset();
            $('#edPk').val('');
            $('#edSocietyBody').html('<tr id="edSocietyPlaceholder"><td colspan="5" class="text-center text-body-secondary py-4">Select a nomination drive to load its societies.</td></tr>');
            $('#edToggleAll').prop('checked', false).prop('indeterminate', false);
            $('input[name="same_date_for_all"][value="1"]').prop('checked', true);
            $('input[name="same_time_for_all"][value="1"]').prop('checked', true);
            syncAllSocietyRows();
            edClearErrors();
            // Native reset/val() do not reach Select2 — redraw it.
            $form.find('select.cs-select2').trigger('change.select2');
        }

        function edSetMode(isEdit) {
            $('#edFormModalLabel').text(isEdit ? 'Edit Election Drive' : 'Create Election Drive');
            $('#edSubmitBtn').text(isEdit ? 'Update Election Drive' : 'Create Election Drive');
        }

        $('#edAddBtn').on('click', function () {
            edResetForm();
            edSetMode(false);
        });

        var editUrlTemplate = "{{ route('master.election.drive.edit', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .ed-edit-btn', function () {
            var id = $(this).data('id');
            edResetForm();
            edSetMode(true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('edFormModal')).show();

            $.ajax({
                url: editUrlTemplate.replace('__ID__', encodeURIComponent(id)),
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $('#edPk').val(res.pk);
                    $('#edDriveName').val(res.election_drive_name);
                    $('#edNominationDrive').val(String(res.nomination_drive_pk)).trigger('change.select2');
                    $('#edDate').val(res.drive_date);
                    $('#edStartTime').val(res.start_time);
                    $('#edEndTime').val(res.end_time);
                    $('input[name="same_date_for_all"][value="' + (res.same_date_for_all ? '1' : '0') + '"]').prop('checked', true);
                    $('input[name="same_time_for_all"][value="' + (res.same_time_for_all ? '1' : '0') + '"]').prop('checked', true);

                    // The society rows come from the nomination drive, so load
                    // them first and re-apply the saved selection afterwards.
                    loadSocieties(res.nomination_drive_token, function () {
                        (res.societies || []).forEach(function (s) {
                            var $tr = $('tr[data-society-row="' + s.club_society_master_pk + '"]');
                            if (!$tr.length) { return; }
                            $tr.find('.ed-society-checkbox').prop('checked', true);
                        });
                        syncAllSocietyRows();
                        // syncAllSocietyRows clears shared fields — set values after.
                        (res.societies || []).forEach(function (s) {
                            var $tr = $('tr[data-society-row="' + s.club_society_master_pk + '"]');
                            if (s.drive_date) { $tr.find('.ed-society-date').val(s.drive_date); }
                            if (s.start_time) { $tr.find('.ed-society-start').val(s.start_time); }
                            if (s.end_time) { $tr.find('.ed-society-end').val(s.end_time); }
                        });

                        var total = $('.ed-society-checkbox').length;
                        var on = $('.ed-society-checkbox:checked').length;
                        $('#edToggleAll').prop('checked', total > 0 && on === total)
                                         .prop('indeterminate', on > 0 && on < total);
                    });
                },
                error: function () {
                    $alert.removeClass('d-none alert-success').addClass('alert-danger')
                          .text('Could not load this drive. Please close and try again.');
                }
            });
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            edClearErrors();

            if (!$('.ed-society-checkbox:checked').length) {
                $form.find('.invalid-feedback[data-field="society_pks"]').text('Select at least one society.');
                return;
            }

            var $submit = $('#edSubmitBtn');
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
                        var instance = bootstrap.Modal.getInstance(document.getElementById('edFormModal'));
                        if (instance) { instance.hide(); }
                        edResetForm();
                    }, 900);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            var $target = $form.find('.invalid-feedback[data-field="' + field + '"]');
                            if ($target.length) {
                                $target.text(errors[field][0]);
                                $target.closest('td, .col-12, .col-md-6, .col-md-4').find('input, select').first().addClass('is-invalid');
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

        document.getElementById('edFormModal').addEventListener('hidden.bs.modal', function () {
            edResetForm();
            edSetMode(false);
        });

        /* ---- Publish Election / Publish Result ---- */
        var publishUrlTemplate = "{{ route('master.election.drive.publish', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .ed-publish-btn', function () {
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: publishUrlTemplate.replace('__ID__', encodeURIComponent($btn.data('id'))),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    field: $btn.data('field'),
                    value: $btn.data('next')
                },
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Updated', text: (res && res.message) || 'Status updated.' });
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
        var deleteUrlTemplate = "{{ route('master.election.drive.destroy', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .ed-delete-btn', function () {
            var $btn = $(this);
            var name = $btn.data('label') || 'this election drive';

            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete "' + name + '"? This cannot be undone.',
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
                    data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        Swal.fire('Deleted!', (res && res.message) || 'Election drive deleted.', 'success');
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

        syncAllSocietyRows();
    });
</script>
@endpush
