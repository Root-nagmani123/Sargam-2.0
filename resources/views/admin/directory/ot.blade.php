@extends('admin.layouts.master')

@section('title', 'OT Directory')

@push('styles')
{{-- Select2 for the programme filter — the Archived tab lists ~140 courses, which
     a plain <select> can't be scanned. Only the CSS is per-page; select2.full.min.js
     is already global (footer.blade.php). select2-theme.css MUST come second. --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">

{{-- Directory module chrome. Shared with the LBSNAA directory when that page is
     migrated, so it lives in a file rather than an inline <style>. --}}
<link rel="stylesheet"
      href="{{ asset('css/directory-admin.css') }}?v={{ @filemtime(public_path('css/directory-admin.css')) ?: time() }}">
@endpush

@section('content')
@php
    // Tab links deliberately drop course_id: the other tab's programme list is
    // disjoint from this one's, so the controller re-picks its first programme.
    $tabUrl = fn (string $tab) => route('admin.directory.ot', ['status' => $tab]);
@endphp

<div class="container-fluid dir-page">
    <x-breadcrum title="OT Directory"></x-breadcrum>

    {{-- Active / Archived + Download, above the card (§1). The pills are real
         links, not JS filters: each tab is a different programme list AND a
         different set of rows, so the active state is resolved server-side. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter programmes by status">
            <li class="nav-item" role="presentation">
                <a href="{{ $tabUrl('active') }}"
                   class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $status === 'active' ? 'active' : '' }}"
                   @if($status === 'active') aria-current="page" @endif>Active</a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ $tabUrl('archive') }}"
                   class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $status === 'archive' ? 'active' : '' }}"
                   @if($status === 'archive') aria-current="page" @endif>Archived</a>
            </li>
        </ul>

        {{-- ?status / ?course_id / ?q / ?sort / ?dir / ?cols are stamped on every
             link by dirUpdateExportLinks(), so a download or a printout carries
             the programme, search term, ordering and columns the grid is showing.
             Print is a server-rendered view, not window.print() on this page, so
             it comes off the same query as the other four (§1).

             Gated to match the 'directory.export' middleware on the routes: the
             grid is open to everyone, the bulk downloads are not. --}}
        @if(isSidebarPrivilegedUser())
        <div class="d-flex flex-wrap justify-content-end gap-2 dir-secondary-actions">

            <div class="dropdown">
                <button type="button" id="dirOtDownloadToggle"
                        class="btn programme-dt-btn-columns dropdown-toggle border-0 text-primary"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2"
                    aria-labelledby="dirOtDownloadToggle">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="dirOtCsvLink"
                           href="{{ route('admin.directory.ot.export', ['format' => 'csv', 'status' => $status]) }}">
                            <i class="bi bi-filetype-csv text-success" aria-hidden="true"></i>
                            <span>Download CSV</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="dirOtExcelLink"
                           href="{{ route('admin.directory.ot.export', ['format' => 'excel', 'status' => $status]) }}">
                            <i class="bi bi-file-earmark-excel text-success" aria-hidden="true"></i>
                            <span>Download Excel</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="dirOtPdfLink"
                           href="{{ route('admin.directory.ot.export', ['format' => 'pdf', 'status' => $status]) }}">
                            <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
                            <span>Download PDF</span>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-2"></li>
                    <li>
                        {{-- Below the divider because it is a different KIND of
                             export: every column, whatever the Columns modal says. --}}
                        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2" id="dirOtFullLink"
                           href="{{ route('admin.directory.ot.export', ['format' => 'full', 'status' => $status]) }}">
                            <i class="bi bi-database text-secondary" aria-hidden="true"></i>
                            <span>Full Details (Excel)</span>
                        </a>
                    </li>
                </ul>
            </div>
             <a href="{{ route('admin.directory.ot.export', ['format' => 'print', 'status' => $status]) }}"
               id="dirOtPrintLink" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
        @endif
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: filters left, columns + search right (§2). The search slot
                 is left empty on purpose — datatable-global-ui.js moves DataTables'
                 own filter into it, so the grid filters as you type instead of
                 reloading the page. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select dir-filter-wide">
                        <select id="dirOtCourse" class="form-select" aria-label="Programme name">
                            {{-- Active lists only programmes in session, so between one
                                 programme ending and the next starting the list is empty.
                                 Say so here rather than render a blank box. --}}
                            @if($courses->isEmpty())
                                <option value="" disabled selected>
                                    {{ $status === 'archive' ? 'No archived programme' : 'No programme currently running' }}
                                </option>
                            @endif
                            @foreach($courses as $course)
                                <option value="{{ $course->pk }}"
                                        title="{{ $course->course_name }}"
                                        @selected((int) $selectedCourseId === (int) $course->pk)>
                                    {{ $course->couse_short_name ?: $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="dirOtReset">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="dirOtBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#dirOtColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="dirOtDtSearch" class="programme-dt-search" data-dt-search-for="dirOtTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and paging are all SQL, so only the
                         page on screen ever reaches the browser and the grid does not
                         grow with the programme. The global enhancer supplies the
                         search slot and the footer. --}}
                    <table id="dirOtTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                {{-- Photo + Name + OT Code are one identity column. It
                                     sorts by name; it also covers TWO export columns —
                                     see EXPORT_COLUMN_KEYS below. --}}
                                <th scope="col" class="dir-col-identity">Name</th>
                                <th scope="col" class="dir-col-tight">Room No.</th>
                                <th scope="col" class="dir-col-tight">Room Extension No.</th>
                                <th scope="col" class="dir-col-email">Email ID</th>
                                <th scope="col" class="dir-col-text">Course Name</th>
                                <th scope="col" class="dir-col-text">Cadre Name</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates, datatable-global-ui.js
                     fills this in with the pager and "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="dirOtTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="dirOtColumnVisibilityModal" tabindex="-1" aria-labelledby="dirOtColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="dirOtColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="dirOtColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var STATUS = @json($status);

    /* DataTable (server-side) -----------------------------------------------
       Search, sort, paging and the footer are DataTables', and every one is
       answered by the server: a draw fetches only the page being shown.
       `sargamServerOrder` keeps ordering on the server too, so a header click
       re-sorts the WHOLE programme rather than the visible page. */
    var dt = $('#dirOtTable').DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        // 400ms after the last keystroke — search as you type, one query per pause.
        searchDelay: 400,
        order: [[1, 'asc']],
        ajax: {
            url: '{{ route('admin.directory.ot.data') }}',
            data: function (d) {
                // The programme filter and the tab aren't DataTables state, so
                // they ride along on every draw.
                d.status = STATUS;
                d.course_id = $('#dirOtCourse').val() || '';
            }
        },
        /* `name` is the sort key the controller whitelists (OT_SORTABLE_COLUMNS);
           an empty one means "not sortable in SQL". Course Name is constant here
           (the grid is always scoped to one programme) and the Room columns hold
           a placeholder, so neither gets a caret. */
        columns: [
            { data: 'sno', name: '', orderable: false, searchable: false },
            { data: 'identity', name: 'name' },
            { data: 'room_no', name: '', orderable: false, searchable: false },
            { data: 'room_ext', name: '', orderable: false, searchable: false },
            { data: 'email', name: 'email', className: 'dir-col-wrap dir-col-email' },
            { data: 'course', name: '', orderable: false, className: 'dir-col-wrap dir-col-text' },
            { data: 'cadre', name: 'cadre', className: 'dir-col-wrap dir-col-text' }
        ],
        language: {
            emptyTable: '<div class="dir-empty">' +
                '<i class="bi bi-inbox d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Officer Trainees Found</h6>' +
                '<p class="mb-0 small">This programme has no officer trainees yet.</p>' +
                '</div>',
            zeroRecords: '<div class="dir-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Officer Trainees Found</h6>' +
                '<p class="mb-0 small">No officer trainee matches your search.</p>' +
                '</div>'
        }
    });

    /* An image already in the browser cache can finish before its inline onload
       is live, which would leave a good photo sitting at opacity 0 over the
       initials. Settle those after every draw — the rows are replaced each time. */
    dt.on('draw.dt', function () {
        $('#dirOtTable').find('.dir-avatar__img').each(function () {
            if (!this.complete) { return; }
            if (this.naturalWidth > 0) { this.classList.add('is-loaded'); } else { $(this).remove(); }
        });
    });

    /* Programme filter -------------------------------------------------------
       Select2 signals a pick with jQuery's .trigger('change'), which only reaches
       jQuery-bound handlers — so this listener must stay jQuery-bound, not
       addEventListener. minimumResultsForSearch keeps the search box off the
       Active tab (a couple of programmes) and on for Archived (~140). */
    if ($.fn.select2) {
        $('#dirOtCourse').select2({
            width: '100%',
            placeholder: 'Programme Name',
            minimumResultsForSearch: 10,
        });
    }

    $('#dirOtCourse').on('change', function () {
        // Redraw from page 1 rather than reloading the page — the grid is AJAX now.
        dt.ajax.reload(null, true);
        dirUpdateExportLinks();
    });

    $('#dirOtReset').on('click', function () {
        // Stay on the tab; clear the programme, search, ordering and page length.
        var $course = $('#dirOtCourse');
        $course.val($course.find('option:first').val());
        if ($.fn.select2) { $course.trigger('change.select2'); }
        dt.search('').order([[1, 'asc']]).page.len(10).draw();
        dirUpdateExportLinks();
    });

    /* Column visibility (DataTables column API) ------------------------------
       Stored by LABEL, not index — an index points at a different column the
       moment one is added, silently hiding the wrong one. */
    var COLVIS_KEY = 'sargam.otDirectory.hiddenCols.{{ auth()->id() ?? 'guest' }}';

    /* Header index -> the export column keys that header covers, for ?cols=.
       The identity column shows Photo + Name + OT Code and the spreadsheet keeps
       Name and OT Code as separate columns, so hiding it must drop both.
       Positional: adding a table column means adding an entry here too. */
    var EXPORT_COLUMN_KEYS = [
        ['sno'],
        ['name', 'ot_code'],
        ['room_no'],
        ['room_ext'],
        ['email'],
        ['course'],
        ['cadre']
    ];
    var EXPORT_COL_COUNT = EXPORT_COLUMN_KEYS.reduce(function (n, keys) { return n + keys.length; }, 0);

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COLVIS_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];   // private mode / storage disabled / corrupt value
        }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COLVIS_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    /* Keep every export link carrying exactly what the grid is showing: the
       programme, the search term (DataTables holds it, the export reads ?q), the
       ordering, and the columns still visible. */
    function dirUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var cols = EXPORT_COLUMN_KEYS[this.index()] || [];
            if (this.visible()) { cols.forEach(function (key) { keys.push(key); }); }
        });

        var order = dt.order()[0] || [1, 'asc'];
        var column = dt.settings()[0].aoColumns[order[0]];
        var sortKey = (column && column.sName) || '';
        var term = dt.search() || '';
        var courseId = $('#dirOtCourse').val() || '';

        // Full Details is deliberately NOT column-filtered — it is the everything
        // dump, so it gets the same scope but never a ?cols=.
        var linkIds = ['dirOtCsvLink', 'dirOtExcelLink', 'dirOtPdfLink', 'dirOtPrintLink', 'dirOtFullLink'];
        linkIds.forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var params = new URLSearchParams();
            params.set('status', STATUS);
            if (courseId) { params.set('course_id', courseId); }
            if (term !== '') { params.set('q', term); }
            if (sortKey !== '') { params.set('sort', sortKey); params.set('dir', order[1]); }
            // Omit ?cols= while nothing is hidden — the server reads that as "all".
            if (id !== 'dirOtFullLink' && keys.length !== EXPORT_COL_COUNT) {
                params.set('cols', keys.join(','));
            }
            link.href = link.href.split('?')[0] + '?' + params.toString();
        });
    }

    // Search-as-you-type and re-ordering have to re-stamp the links, not just
    // redraw the grid.
    dt.on('search.dt order.dt', dirUpdateExportLinks);

    function buildColumnToggles() {
        var $grid = $('#dirOtColumnToggleGrid');
        var hidden = getHiddenCols();

        dt.columns().every(function () {
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (title) { this.visible(hidden.indexOf(title) === -1, false); }
        });
        dt.columns.adjust();

        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var index = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'dirOtColvis' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $checkbox.on('change', function () {
                var cols = getHiddenCols();
                var pos = cols.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) { cols.splice(pos, 1); }
                } else if (pos === -1) {
                    cols.push(title);
                }
                persistHiddenCols(cols);
                dt.column(index).visible(this.checked, false);
                dt.columns.adjust();
                dirUpdateExportLinks();
            });

            // Built as jQuery objects, not concatenated HTML — .text() escapes.
            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr({ 'for': inputId, title: title })
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });
    }

    buildColumnToggles();
    // Stamp the restored column state onto the export links on first paint too.
    dirUpdateExportLinks();
});
</script>
@endpush
