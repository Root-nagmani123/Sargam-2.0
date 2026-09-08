@extends('admin.layouts.master')

@section('title', 'Club/ Society Programme Mapping')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
{{-- Shares the .cs-master-page CSS scope with the two Club/ Society masters
     (icon-over-label Edit/Delete), defined once in public/css/custom.css. --}}
<div class="container-fluid cs-master-page cspm-page">
    <x-breadcrum title="Club/ Society Programme Mapping">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="cspmAddBtn" data-bs-toggle="modal" data-bs-target="#cspmFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Map Club/ Society Programme</span>
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
                        id="cspmFilterActive" data-cspm-status="active" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="cspmFilterArchive" data-cspm-status="archive" aria-pressed="false">Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('master.club.society.programme.mapping.export') }}" id="cspmDownloadBtn"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </a>
            <a href="{{ route('master.club.society.programme.mapping.print') }}" id="cspmPrintBtn"
               target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
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
                        <select id="cspmCourseFilterSelect" class="cs-select2 form-select" aria-label="Filter by course name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}" data-status="active">
                                    {{ $course->couse_short_name ?: $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="cspmRemoveFilter">
                        Remove Filter
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="cspmBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#cspmColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="cspmDtSearch" class="programme-dt-search" data-dt-search-for="clubsocietyprogrammemapping-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="cspmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="clubsocietyprogrammemapping-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Map / Edit Club Society Programme Modal -->
<div class="modal fade" id="cspmFormModal" tabindex="-1" aria-labelledby="cspmFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="cspmMappingForm" action="{{ route('master.club.society.programme.mapping.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="cspmFormModalLabel">Map Club/ Society Programme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cspmFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="mb-3">
                        <label for="cspmCourse" class="form-label fw-semibold">Course Name <span class="text-danger">*</span></label>
                        <select class="cs-select2 form-select" id="cspmCourse" name="course_master_pk" required>
                            <option value="">Select Course</option>
                            <optgroup label="Active" id="cspmCourseActiveGroup">
                                @foreach ($courses as $course)
                                    <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                                @endforeach
                            </optgroup>
                            @if (count($archiveCourses))
                                <optgroup label="Archived" id="cspmCourseArchiveGroup">
                                    @foreach ($archiveCourses as $course)
                                        <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <div class="invalid-feedback" data-field="course_master_pk"></div>
                    </div>

                    <div class="mb-0">
                        {{-- The design labels this "Club/ Society Role Name", but the items
                             listed under it are clubs, not roles — labelled by what it holds. --}}
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <label class="form-label fw-semibold mb-0">Club/ Society Name <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-3">
                                <button type="button" class="btn btn-link p-0 small text-decoration-none" id="cspmSelectAll">Select all</button>
                                <button type="button" class="btn btn-link p-0 small text-decoration-none" id="cspmClearAll">Clear</button>
                            </div>
                        </div>

                        {{-- CSS multi-column so the list reads DOWN each column
                             (as in the design), not across. --}}
                        <div id="cspmClubGrid" class="cspm-club-grid">
                            @foreach ($clubSocieties as $club)
                                <label class="d-flex align-items-start gap-2 mb-0 cspm-club-option" for="cspmClub{{ $club->pk }}">
                                    <input type="checkbox" class="form-check-input m-0 cspm-club-checkbox"
                                           id="cspmClub{{ $club->pk }}" name="club_society_pks[]" value="{{ $club->pk }}">
                                    <span>{{ $club->club_society_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="invalid-feedback d-block" data-field="club_society_pks"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-danger rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="cspmSubmitBtn">Map Club/ Society Programme</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="cspmColumnVisibilityModal" tabindex="-1" aria-labelledby="cspmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="cspmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="cspmColumnToggleGrid"></div>
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
    /* Read by the DataTable's ajax .data callback (see the DataTable class) so
       the pill + course filter travel with every draw, including pagination. */
    var cspmStatus = 'active';
    var cspmCourse = '';
    window.cspmStatusFilter = function () { return cspmStatus; };
    window.cspmCourseFilter = function () { return cspmCourse; };

    $(document).ready(function () {
        var TABLE_ID = '#clubsocietyprogrammemapping-table';

        /* Search box, pagination and the "Showing N of M items" count are
           relocated into #cspmDtSearch / #cspmDtFooter by the global enhancer
           (public/js/datatable-global-ui.js). Do NOT rebuild them here. */

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
        $('.programme-status-pill[data-cspm-status]').on('click', function () {
            var $btn = $(this);
            if ($btn.hasClass('active')) { return; }

            $('.programme-status-pill[data-cspm-status]')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $btn.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

            cspmStatus = $btn.data('cspm-status');

            // The course dropdown lists only courses of the selected status, so
            // repopulate it and drop a now-irrelevant selection.
            var $sel = $('#cspmCourseFilterSelect');
            $sel.find('option:not(:first)').remove();
            (COURSES[cspmStatus] || []).forEach(function (c) {
                $sel.append($('<option></option>').attr('value', c.pk).text(c.name));
            });
            cspmCourse = '';
            $sel.val('').trigger('change.select2');

            syncExportLinks();
            reload();
        });

        /* ---- Course filter + Remove Filter ---- */
        $('#cspmCourseFilterSelect').on('change', function () {
            cspmCourse = $(this).val() || '';
            syncExportLinks();
            reload();
        });

        $('#cspmRemoveFilter').on('click', function () {
            cspmCourse = '';
            $('#cspmCourseFilterSelect').val('').trigger('change.select2');
            var t = dt();
            if (t) { t.search(''); }
            syncExportLinks();
            reload();
        });

        /* Download / Print must export what is on screen, not the whole table. */
        function syncExportLinks() {
            var qs = '?status_filter=' + encodeURIComponent(cspmStatus)
                   + (cspmCourse ? '&course_master_pk=' + encodeURIComponent(cspmCourse) : '');
            $('#cspmDownloadBtn').attr('href', "{{ route('master.club.society.programme.mapping.export') }}" + qs);
            $('#cspmPrintBtn').attr('href', "{{ route('master.club.society.programme.mapping.print') }}" + qs);
        }
        syncExportLinks();

        /* ---- Column show / hide ---- */
        var cspmColStorageKey = 'cspmGrid:hiddenColumns:v1';

        function cspmGetHiddenCols() {
            try {
                var raw = localStorage.getItem(cspmColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }

        function cspmPersistHiddenCols(arr) {
            try { localStorage.setItem(cspmColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupCspmColumns(table) {
            if (!table) { return; }
            var hidden = cspmGetHiddenCols();

            table.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            table.columns.adjust();

            var $grid = $('#cspmColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            table.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }

                var inputId = 'cspmcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = cspmGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    cspmPersistHiddenCols(h);
                    table.column(idx).visible(this.checked, false);
                    table.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        setTimeout(function () { setupCspmColumns(dt()); }, 150);

        /* ---- Add / Edit modal ---- */
        var $form  = $('#cspmMappingForm');
        var $alert = $('#cspmFormAlert');

        function cspmClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');
            $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();
        }

        function cspmResetForm() {
            $form[0].reset();
            $('.cspm-club-checkbox').prop('checked', false);
            $('#cspmCourse').prop('disabled', false).val('');
            $form.find('input[name="course_master_pk"][type="hidden"]').remove();
            cspmClearErrors();
            // Native reset/val() do not reach Select2 — redraw it.
            $form.find('select.cs-select2').trigger('change.select2');
        }

        function cspmSetMode(isEdit) {
            $('#cspmFormModalLabel').text(isEdit ? 'Edit Club/ Society Programme Mapping' : 'Map Club/ Society Programme');
            $('#cspmSubmitBtn').text(isEdit ? 'Update Mapping' : 'Map Club/ Society Programme');
        }

        $('#cspmAddBtn').on('click', function () {
            cspmResetForm();
            cspmSetMode(false);
        });

        $('#cspmSelectAll').on('click', function () { $('.cspm-club-checkbox').prop('checked', true); });
        $('#cspmClearAll').on('click', function () { $('.cspm-club-checkbox').prop('checked', false); });

        // Edit: fetch the course's current club set and tick those boxes.
        var editUrlTemplate = "{{ route('master.club.society.programme.mapping.edit', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .cspm-edit-btn', function () {
            var id = $(this).data('id');
            cspmResetForm();
            cspmSetMode(true);

            var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('cspmFormModal'));
            modal.show();

            $.ajax({
                url: editUrlTemplate.replace('__ID__', encodeURIComponent(id)),
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    $('#cspmCourse').val(String(res.course_master_pk));
                    // The course identifies the row being edited — lock it, and
                    // post it as a hidden field since disabled inputs don't submit.
                    $('#cspmCourse').prop('disabled', true).trigger('change.select2');
                    $form.append($('<input type="hidden" name="course_master_pk">').val(res.course_master_pk));

                    (res.club_society_pks || []).forEach(function (pk) {
                        $('#cspmClub' + pk).prop('checked', true);
                    });
                },
                error: function () {
                    $alert.removeClass('d-none alert-success').addClass('alert-danger')
                          .text('Could not load this mapping. Please close and try again.');
                }
            });
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            cspmClearErrors();

            if (!$('.cspm-club-checkbox:checked').length) {
                $form.find('.invalid-feedback[data-field="club_society_pks"]').text('Select at least one club/ society.');
                return;
            }

            var $submit = $('#cspmSubmitBtn');
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
                        var instance = bootstrap.Modal.getInstance(document.getElementById('cspmFormModal'));
                        if (instance) { instance.hide(); }
                        cspmResetForm();
                    }, 900);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            var key = field.replace(/\.\d+$/, '').replace(/\.\*$/, '');
                            $form.find('[name="' + key + '"]').addClass('is-invalid');
                            $form.find('.invalid-feedback[data-field="' + key + '"]').text(errors[field][0]);
                        });
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

        document.getElementById('cspmFormModal').addEventListener('hidden.bs.modal', function () {
            cspmResetForm();
            cspmSetMode(false);
        });

        /* ---- Delete (removes every club mapped to that course) ---- */
        var deleteUrlTemplate = "{{ route('master.club.society.programme.mapping.destroy', ['id' => '__ID__']) }}";

        $(document).on('click', TABLE_ID + ' .cspm-delete-btn', function () {
            var $btn = $(this);
            var id   = $btn.data('id');
            var name = $btn.data('course') || 'this course';

            Swal.fire({
                title: 'Are you sure?',
                text: 'Remove every club/ society mapped to "' + name + '"? This cannot be undone.',
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
    });
</script>
@endpush
