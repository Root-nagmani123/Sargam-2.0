@extends('admin.layouts.master')

@section('title', 'Officer Bearers')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
{{-- Shares the .cs-master-page CSS scope with the rest of the Club/ Society
     module, defined once in public/css/custom.css. --}}
<div class="container-fluid cs-master-page ob-page">
    {{-- Read-only listing: no Add button, matching the design. --}}
    <x-breadcrum title="Officer Bearers" />

    <x-session_message />

    {{-- Active / Archived follows the COURSE's own status, the same split used
         across this module. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter officer bearers by course status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill active"
                        id="obFilterActive" data-ob-status="active" aria-pressed="true" aria-current="true">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill"
                        id="obFilterArchive" data-ob-status="archive" aria-pressed="false">Archived</button>
            </li>
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('master.office.bearer.export') }}" id="obDownloadBtn"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Download</span>
            </a>
            <a href="{{ route('master.office.bearer.print') }}" id="obPrintBtn"
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
                        <select id="obCourseFilterSelect" class="cs-select2 form-select" aria-label="Filter by course name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->pk }}">{{ $course->couse_short_name ?: $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select id="obClubFilterSelect" class="cs-select2 form-select" aria-label="Filter by club/ society">
                            <option value="">Club/ Society/ Association</option>
                            @foreach ($clubSocieties as $club)
                                <option value="{{ $club->pk }}">{{ $club->club_society_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="obRemoveFilter">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="obBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#obColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="obDtSearch" class="programme-dt-search" data-dt-search-for="officebearer-table"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="obDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="officebearer-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="obColumnVisibilityModal" tabindex="-1" aria-labelledby="obColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="obColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="obColumnToggleGrid"></div>
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
    /* Read by the DataTable's ajax data callback so the pill and both filters
       travel with every draw, paging included. */
    var obStatus = 'active';
    var obCourse = '';
    var obClub = '';
    window.obStatusFilter = function () { return obStatus; };
    window.obCourseFilter = function () { return obCourse; };
    window.obClubFilter = function () { return obClub; };

    $(document).ready(function () {
        var TABLE_ID = '#officebearer-table';

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
        $('.programme-status-pill[data-ob-status]').on('click', function () {
            var $btn = $(this);
            if ($btn.hasClass('active')) { return; }

            $('.programme-status-pill[data-ob-status]')
                .removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
            $btn.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

            obStatus = $btn.data('ob-status');

            // The course list is per-status, so repopulate and drop a stale pick.
            var $sel = $('#obCourseFilterSelect');
            $sel.find('option:not(:first)').remove();
            (COURSES[obStatus] || []).forEach(function (c) {
                $sel.append($('<option></option>').attr('value', c.pk).text(c.name));
            });
            obCourse = '';
            $sel.val('').trigger('change.select2');

            syncExportLinks();
            reload(true);
        });

        /* ---- Filters ---- */
        $('#obCourseFilterSelect').on('change', function () {
            obCourse = $(this).val() || '';
            syncExportLinks();
            reload(true);
        });

        $('#obClubFilterSelect').on('change', function () {
            obClub = $(this).val() || '';
            syncExportLinks();
            reload(true);
        });

        $('#obRemoveFilter').on('click', function () {
            obCourse = '';
            obClub = '';
            $('#obCourseFilterSelect, #obClubFilterSelect').val('').trigger('change.select2');
            var t = dt();
            if (t) { t.search(''); }
            syncExportLinks();
            reload(true);
        });

        function syncExportLinks() {
            var qs = '?status_filter=' + encodeURIComponent(obStatus)
                   + (obCourse ? '&course_master_pk=' + encodeURIComponent(obCourse) : '')
                   + (obClub ? '&club_society_master_pk=' + encodeURIComponent(obClub) : '');
            $('#obDownloadBtn').attr('href', "{{ route('master.office.bearer.export') }}" + qs);
            $('#obPrintBtn').attr('href', "{{ route('master.office.bearer.print') }}" + qs);
        }
        syncExportLinks();

        /* ---- Column show / hide ---- */
        var obColKey = 'obGrid:hiddenColumns:v1';
        function obHidden() {
            try {
                var raw = localStorage.getItem(obColKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }
        function setupObColumns(table) {
            if (!table) { return; }
            var hidden = obHidden();
            table.columns().every(function () {
                this.visible(hidden.indexOf(this.index()) === -1, false);
            });
            table.columns.adjust();

            var $grid = $('#obColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            table.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                var inputId = 'obcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
                $cb.on('change', function () {
                    var h = obHidden();
                    var pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); }
                    else { if (pos === -1) h.push(idx); }
                    try { localStorage.setItem(obColKey, JSON.stringify(h)); } catch (e) {}
                    table.column(idx).visible(this.checked, false);
                    table.columns.adjust();
                });
                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }
        setTimeout(function () { setupObColumns(dt()); }, 150);
    });
</script>
@endpush
