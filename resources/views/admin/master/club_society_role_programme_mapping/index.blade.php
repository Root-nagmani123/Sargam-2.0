@extends('admin.layouts.master')

@section('title', 'Club/ Society Role Programme Mapping')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
{{-- Shares the .cs-master-page CSS scope with the rest of the Club/ Society
     module (icon-over-label actions), defined once in public/css/custom.css. --}}
<div class="container-fluid cs-master-page csrpm-page">
    <x-breadcrum title="Club/ Society Role Programme Mapping">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="csrpmAddBtn" data-bs-toggle="modal" data-bs-target="#csrpmFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Map Club/ Society Role Programme</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Active / Archived follows the COURSE's own status, the same split Course
         Master and Attendance use. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter mappings by course status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        id="csrpmFilterActive" data-csrpm-status="active" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="csrpmFilterArchive" data-csrpm-status="archive" aria-pressed="false">Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('master.club.society.role.programme.mapping.export') }}" id="csrpmDownloadBtn"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </a>
            <a href="{{ route('master.club.society.role.programme.mapping.print') }}" id="csrpmPrintBtn"
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
                        <select id="csrpmCourseFilterSelect" class="cs-select2 form-select" aria-label="Filter by course name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="csrpmRemoveFilter">
                        Remove Filter
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="csrpmBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#csrpmColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="csrpmDtSearch" class="programme-dt-search" data-dt-search-for="clubsocietyroleprogrammemapping-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="csrpmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="clubsocietyroleprogrammemapping-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Map / Edit Club Society Role Programme Modal -->
<div class="modal fade" id="csrpmFormModal" tabindex="-1" aria-labelledby="csrpmFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="csrpmMappingForm" action="{{ route('master.club.society.role.programme.mapping.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="csrpmFormModalLabel">Map Club/ Society Role Programme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="csrpmFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="csrpmCourse" class="form-label fw-semibold">Course Name <span class="text-danger">*</span></label>
                            <select class="cs-select2 form-select" id="csrpmCourse" name="course_master_pk" required>
                                <option value="">Select Course</option>
                                <optgroup label="Active" id="csrpmCourseActiveGroup">
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

                        {{-- Not in the supplied design, but the grid keys a row by
                             (course, club) and shows a different role set per club,
                             so the club has to be chosen here. --}}
                        <div class="col-12 col-md-6">
                            <label for="csrpmClub" class="form-label fw-semibold">Club/ Society Name <span class="text-danger">*</span></label>
                            <select class="cs-select2 form-select" id="csrpmClub" name="club_society_master_pk" required>
                                <option value="">Select Club/ Society</option>
                                @foreach ($clubSocieties as $club)
                                    <option value="{{ $club->pk }}">{{ $club->club_society_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-field="club_society_master_pk"></div>
                        </div>
                    </div>

                    <div class="invalid-feedback d-block mb-2" data-field="role_pks"></div>

                    <div class="table-responsive csrpm-role-table-wrap">
                        <table class="table align-middle mb-0 csrpm-role-table">
                            <thead>
                                <tr>
                                    <th style="width:3rem;" class="text-center">
                                        <input type="checkbox" class="form-check-input m-0" id="csrpmToggleAll"
                                               aria-label="Select all roles">
                                    </th>
                                    <th>Role Name</th>
                                    <th style="width:11rem;">Number of Post</th>
                                    <th style="width:13rem;">Required Nomination</th>
                                    <th style="width:12rem;">Number of Nomination</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr data-role-row="{{ $role->pk }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input m-0 csrpm-role-checkbox"
                                                   id="csrpmRole{{ $role->pk }}" name="role_pks[]" value="{{ $role->pk }}"
                                                   aria-label="Include {{ $role->club_society_role_name }}">
                                        </td>
                                        <td>
                                            <label class="mb-0" for="csrpmRole{{ $role->pk }}">{{ $role->club_society_role_name }}</label>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control csrpm-post-input"
                                                   name="number_of_post[{{ $role->pk }}]" min="1" max="9999" value="1"
                                                   aria-label="Number of posts for {{ $role->club_society_role_name }}" disabled>
                                            <div class="invalid-feedback" data-field="number_of_post.{{ $role->pk }}"></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input csrpm-nom-radio" type="radio"
                                                           name="required_nomination[{{ $role->pk }}]" value="1"
                                                           id="csrpmNomYes{{ $role->pk }}" disabled>
                                                    <label class="form-check-label" for="csrpmNomYes{{ $role->pk }}">Yes</label>
                                                </div>
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input csrpm-nom-radio" type="radio"
                                                           name="required_nomination[{{ $role->pk }}]" value="0"
                                                           id="csrpmNomNo{{ $role->pk }}" checked disabled>
                                                    <label class="form-check-label" for="csrpmNomNo{{ $role->pk }}">No</label>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control csrpm-nom-input"
                                                   name="number_of_nomination[{{ $role->pk }}]" min="1" max="9999"
                                                   aria-label="Number of nominations for {{ $role->club_society_role_name }}" disabled>
                                            <div class="invalid-feedback" data-field="number_of_nomination.{{ $role->pk }}"></div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-body-secondary py-4">
                                            No roles defined yet — add them under Define Club/ Society Role first.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-danger rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="csrpmSubmitBtn">Map Club/ Society Role Programme</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View (read-only) Modal: the grid cannot show posts / nominations -->
<div class="modal fade" id="csrpmViewModal" tabindex="-1" aria-labelledby="csrpmViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold mb-0" id="csrpmViewModalLabel">Mapping Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-3">
                    <dt class="col-sm-4 text-body-secondary fw-normal">Course Name</dt>
                    <dd class="col-sm-8 fw-semibold" id="csrpmViewCourse">—</dd>
                    <dt class="col-sm-4 text-body-secondary fw-normal">Club/Society/Association</dt>
                    <dd class="col-sm-8 fw-semibold mb-0" id="csrpmViewClub">—</dd>
                </dl>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 csrpm-role-table">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th class="text-center">Number of Post</th>
                                <th class="text-center">Required Nomination</th>
                                <th class="text-center">Number of Nomination</th>
                            </tr>
                        </thead>
                        <tbody id="csrpmViewRows">
                            <tr><td colspan="4" class="text-center text-body-secondary py-4">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="csrpmColumnVisibilityModal" tabindex="-1" aria-labelledby="csrpmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="csrpmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="csrpmColumnToggleGrid"></div>
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
    var csrpmStatus = 'active';
    var csrpmCourse = '';
    window.csrpmStatusFilter = function () { return csrpmStatus; };
    window.csrpmCourseFilter = function () { return csrpmCourse; };

    $(document).ready(function () {
        var TABLE_ID = '#clubsocietyroleprogrammemapping-table';

        var COURSES = {
            active:  @json($courses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->couse_short_name ?: $c->course_name])->values()),
            archive: @json($archiveCourses->map(fn ($c) => ['pk' => $c->pk, 'name' => $c->couse_short_name ?: $c->course_name])->values())
        };

        function dt() {
            return $.fn.DataTable.isDataTable(TABLE_ID) ? $(TABLE_ID).DataTable() : null;
        }

        function reload() {
            var t = dt();
            if (t) { t.ajax.reload(null, true); }
        }

        /* ---- Active / Archived pills ---- */
        $('.programme-status-pill[data-csrpm-status]').on('click', function () {
            var $btn = $(this);
            if ($btn.hasClass('active')) { return; }

            $('.programme-status-pill[data-csrpm-status]')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $btn.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

            csrpmStatus = $btn.data('csrpm-status');

            var $sel = $('#csrpmCourseFilterSelect');
            $sel.find('option:not(:first)').remove();
            (COURSES[csrpmStatus] || []).forEach(function (c) {
                $sel.append($('<option></option>').attr('value', c.pk).text(c.name));
            });
            csrpmCourse = '';
            $sel.val('').trigger('change.select2');

            syncExportLinks();
            reload();
        });

        /* ---- Course filter + Remove Filter ---- */
        $('#csrpmCourseFilterSelect').on('change', function () {
            csrpmCourse = $(this).val() || '';
            syncExportLinks();
            reload();
        });

        $('#csrpmRemoveFilter').on('click', function () {
            csrpmCourse = '';
            $('#csrpmCourseFilterSelect').val('').trigger('change.select2');
            var t = dt();
            if (t) { t.search(''); }
            syncExportLinks();
            reload();
        });

        function syncExportLinks() {
            var qs = '?status_filter=' + encodeURIComponent(csrpmStatus)
                   + (csrpmCourse ? '&course_master_pk=' + encodeURIComponent(csrpmCourse) : '');
            $('#csrpmDownloadBtn').attr('href', "{{ route('master.club.society.role.programme.mapping.export') }}" + qs);
            $('#csrpmPrintBtn').attr('href', "{{ route('master.club.society.role.programme.mapping.print') }}" + qs);
        }
        syncExportLinks();

        /* ---- Column show / hide ---- */
        var csrpmColStorageKey = 'csrpmGrid:hiddenColumns:v1';

        function csrpmGetHiddenCols() {
            try {
                var raw = localStorage.getItem(csrpmColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }

        function csrpmPersistHiddenCols(arr) {
            try { localStorage.setItem(csrpmColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupCsrpmColumns(table) {
            if (!table) { return; }
            var hidden = csrpmGetHiddenCols();

            table.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            table.columns.adjust();

            var $grid = $('#csrpmColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            table.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }

                var inputId = 'csrpmcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = csrpmGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    csrpmPersistHiddenCols(h);
                    table.column(idx).visible(this.checked, false);
                    table.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        setTimeout(function () { setupCsrpmColumns(dt()); }, 150);

        /* ---- Role row enable/disable ----
           A role's inputs only make sense once the role is ticked; a disabled
           input is also not submitted, which keeps the payload to ticked roles. */
        function syncRoleRow($row) {
            var on = $row.find('.csrpm-role-checkbox').is(':checked');
            $row.toggleClass('csrpm-row-on', on);
            $row.find('.csrpm-post-input, .csrpm-nom-radio').prop('disabled', !on);
            syncNominationInput($row);
        }

        function syncNominationInput($row) {
            var roleOn = $row.find('.csrpm-role-checkbox').is(':checked');
            var needs = $row.find('.csrpm-nom-radio[value="1"]').is(':checked');
            var $input = $row.find('.csrpm-nom-input');
            $input.prop('disabled', !(roleOn && needs));
            if (!needs) { $input.val(''); }
        }

        $(document).on('change', '.csrpm-role-checkbox', function () {
            syncRoleRow($(this).closest('tr'));
            var total = $('.csrpm-role-checkbox').length;
            var on = $('.csrpm-role-checkbox:checked').length;
            $('#csrpmToggleAll').prop('checked', total > 0 && on === total)
                                .prop('indeterminate', on > 0 && on < total);
        });

        $(document).on('change', '.csrpm-nom-radio', function () {
            syncNominationInput($(this).closest('tr'));
        });

        $('#csrpmToggleAll').on('change', function () {
            var on = this.checked;
            $('.csrpm-role-checkbox').prop('checked', on).each(function () {
                syncRoleRow($(this).closest('tr'));
            });
            $(this).prop('indeterminate', false);
        });

        /* ---- Add / Edit modal ---- */
        var $form  = $('#csrpmMappingForm');
        var $alert = $('#csrpmFormAlert');

        function csrpmClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function csrpmResetForm() {
            $form[0].reset();
            $('#csrpmCourse, #csrpmClub').prop('disabled', false).val('');
            $form.find('input[type="hidden"][name="course_master_pk"], input[type="hidden"][name="club_society_master_pk"]').remove();
            $('.csrpm-role-checkbox').prop('checked', false);
            $('#csrpmToggleAll').prop('checked', false).prop('indeterminate', false);
            $('.csrpm-post-input').val(1);
            $('.csrpm-nom-input').val('');
            $('.csrpm-nom-radio[value="0"]').prop('checked', true);
            $('#csrpmMappingForm tbody tr[data-role-row]').each(function () { syncRoleRow($(this)); });
            csrpmClearErrors();
            // Native reset/val() do not reach Select2 — redraw it.
            $form.find('select.cs-select2').trigger('change.select2');
        }

        function csrpmSetMode(isEdit) {
            $('#csrpmFormModalLabel').text(isEdit ? 'Edit Club/ Society Role Programme Mapping' : 'Map Club/ Society Role Programme');
            $('#csrpmSubmitBtn').text(isEdit ? 'Update Mapping' : 'Map Club/ Society Role Programme');
        }

        $('#csrpmAddBtn').on('click', function () {
            csrpmResetForm();
            csrpmSetMode(false);
        });

        var showUrlTemplate = "{{ route('master.club.society.role.programme.mapping.show', ['id' => '__ID__']) }}";

        function fetchMapping(id) {
            return $.ajax({
                url: showUrlTemplate.replace('__ID__', encodeURIComponent(id)),
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
        }

        // Edit — prefill the saved role rows; the pair identifies the row, so
        // both selects are locked and posted as hidden fields.
        $(document).on('click', TABLE_ID + ' .csrpm-edit-btn', function () {
            var id = $(this).data('id');
            csrpmResetForm();
            csrpmSetMode(true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('csrpmFormModal')).show();

            fetchMapping(id).done(function (res) {
                $('#csrpmCourse').val(String(res.course_master_pk)).prop('disabled', true).trigger('change.select2');
                $('#csrpmClub').val(String(res.club_society_master_pk)).prop('disabled', true).trigger('change.select2');
                $form.append($('<input type="hidden" name="course_master_pk">').val(res.course_master_pk));
                $form.append($('<input type="hidden" name="club_society_master_pk">').val(res.club_society_master_pk));

                (res.rows || []).forEach(function (row) {
                    var $tr = $('tr[data-role-row="' + row.role_pk + '"]');
                    if (!$tr.length) { return; }
                    $tr.find('.csrpm-role-checkbox').prop('checked', true);
                    $tr.find('.csrpm-post-input').val(row.number_of_post);
                    $tr.find('.csrpm-nom-radio[value="' + (Number(row.required_nomination) ? '1' : '0') + '"]').prop('checked', true);
                    syncRoleRow($tr);
                    if (Number(row.required_nomination)) {
                        $tr.find('.csrpm-nom-input').val(row.number_of_nomination);
                    }
                });

                var total = $('.csrpm-role-checkbox').length;
                var on = $('.csrpm-role-checkbox:checked').length;
                $('#csrpmToggleAll').prop('checked', total > 0 && on === total)
                                    .prop('indeterminate', on > 0 && on < total);
            }).fail(function () {
                $alert.removeClass('d-none alert-success').addClass('alert-danger')
                      .text('Could not load this mapping. Please close and try again.');
            });
        });

        // View — read-only; shows the posts / nominations the grid cannot.
        $(document).on('click', TABLE_ID + ' .csrpm-view-btn', function () {
            var id = $(this).data('id');
            $('#csrpmViewCourse, #csrpmViewClub').text('—');
            $('#csrpmViewRows').html('<tr><td colspan="4" class="text-center text-body-secondary py-4">Loading…</td></tr>');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('csrpmViewModal')).show();

            fetchMapping(id).done(function (res) {
                $('#csrpmViewCourse').text(res.course_name || '—');
                $('#csrpmViewClub').text(res.club_society_name || '—');

                var $body = $('#csrpmViewRows').empty();
                if (!res.rows || !res.rows.length) {
                    $body.html('<tr><td colspan="4" class="text-center text-body-secondary py-4">No roles mapped.</td></tr>');
                    return;
                }
                res.rows.forEach(function (row) {
                    var needs = Number(row.required_nomination);
                    $body.append(
                        $('<tr></tr>')
                            .append($('<td></td>').text(row.role_name))
                            .append($('<td class="text-center"></td>').text(row.number_of_post))
                            .append($('<td class="text-center"></td>').append(
                                $('<span class="badge rounded-1 programme-status-badge"></span>')
                                    .addClass(needs ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                                    .text(needs ? 'Yes' : 'No')
                            ))
                            .append($('<td class="text-center"></td>').text(needs ? (row.number_of_nomination ?? '—') : '—'))
                    );
                });
            }).fail(function () {
                $('#csrpmViewRows').html('<tr><td colspan="4" class="text-center text-danger py-4">Could not load this mapping.</td></tr>');
            });
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            csrpmClearErrors();

            if (!$('.csrpm-role-checkbox:checked').length) {
                $form.find('.invalid-feedback[data-field="role_pks"]').text('Select at least one role.');
                return;
            }

            var $submit = $('#csrpmSubmitBtn');
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
                        var instance = bootstrap.Modal.getInstance(document.getElementById('csrpmFormModal'));
                        if (instance) { instance.hide(); }
                        csrpmResetForm();
                    }, 900);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            var $target = $form.find('.invalid-feedback[data-field="' + field + '"]');
                            if ($target.length) {
                                $target.text(errors[field][0]);
                                $target.closest('td, .col-12').find('input, select').addClass('is-invalid');
                            } else {
                                var key = field.replace(/\.\d+$/, '').replace(/\.\*$/, '');
                                $form.find('[name="' + key + '"]').addClass('is-invalid');
                                $form.find('.invalid-feedback[data-field="' + key + '"]').text(errors[field][0]);
                            }
                        });
                        $alert.removeClass('d-none alert-success').addClass('alert-danger')
                              .text(xhr.responseJSON.message || 'Please correct the highlighted rows.');
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

        document.getElementById('csrpmFormModal').addEventListener('hidden.bs.modal', function () {
            csrpmResetForm();
            csrpmSetMode(false);
        });

        /* ---- Delete (removes every role row for that course + club) ---- */
        var deleteUrlTemplate = "{{ route('master.club.society.role.programme.mapping.destroy', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .csrpm-delete-btn', function () {
            var $btn = $(this);
            var id   = $btn.data('id');
            var name = $btn.data('label') || 'this mapping';

            Swal.fire({
                title: 'Are you sure?',
                text: 'Remove every role mapped for ' + name + '? This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) { return; }

                $btn.prop('disabled', true);

                $.ajax({
                    url: deleteUrlTemplate.replace('__ID__', encodeURIComponent(id)),
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        Swal.fire('Deleted!', (res && res.message) || 'Mapping deleted successfully.', 'success');
                        reload();
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'Something went wrong while deleting.';
                        Swal.fire('Error!', msg, 'error');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });

        // Initial row state (all roles unticked -> inputs disabled).
        $('#csrpmMappingForm tbody tr[data-role-row]').each(function () { syncRoleRow($(this)); });
    });
</script>
@endpush
