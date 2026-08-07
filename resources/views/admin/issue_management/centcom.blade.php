@extends('admin.layouts.master')

@section('title', 'Centcom Assign')

@push('styles')
{{-- Shared Centcom index chrome — the same file All Requests and the master
     grids use, so the whole module stays consistent. See docs/new-design-index-page.md. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // Filters that survive a full page load (deep links). Everything after first
    // paint is carried on the grid's ajax call instead.
    $exportQuery = array_filter([
        'search' => $search,
        'status' => request('status'),
        'category' => request('category'),
        'priority' => request('priority'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="Centcom Assign" :showBack="false">
        <a href="{{ route('admin.issue-management.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Log New Issue</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs (left) + exports (right) — above the card, per §1 --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 ic-secondary-actions">
        <nav class="ic-tabs" aria-label="Issue scope">
            <a href="{{ route('admin.issue-management.index') }}" class="ic-tab">All Requests</a>
            <a href="{{ route('admin.issue-management.index', ['raised_by' => 'self']) }}" class="ic-tab">Raised By You</a>
            <a href="{{ route('admin.issue-management.centcom') }}" class="ic-tab is-active" aria-current="page">Assign to you</a>
        </nav>

        <div class="d-flex flex-wrap gap-2">
            {{-- More than one download format → dropdown, per §1 of the doc. --}}
            <div class="dropdown">
                <button type="button" id="ccDownloadToggle"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="ccDownloadToggle">
                    <li><a class="dropdown-item" id="ccDownloadLink"
                           href="{{ route('admin.issue-management.centcom.export', array_merge(['format' => 'csv'], $exportQuery)) }}">
                            <i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                    <li><a class="dropdown-item" id="ccExcelLink"
                           href="{{ route('admin.issue-management.centcom.export', array_merge(['format' => 'excel'], $exportQuery)) }}">
                            <i class="bi bi-file-earmark-excel me-2" aria-hidden="true"></i>Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item" id="ccPdfLink"
                           href="{{ route('admin.issue-management.centcom.export', array_merge(['format' => 'pdf'], $exportQuery)) }}">
                            <i class="bi bi-file-earmark-pdf me-2" aria-hidden="true"></i>PDF</a></li>
                </ul>
            </div>
            <a href="{{ route('admin.issue-management.centcom.export', array_merge(['format' => 'print'], $exportQuery)) }}"
               id="ccPrintLink"
               target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: filters left, columns + search right (§2).
                 No <form>: every control just redraws the grid over ajax. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 ic-toolbar ic-toolbar--compact">
                <div class="d-flex flex-wrap align-items-center gap-2" id="ccFilters">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select name="status" class="form-select cc-auto-filter" aria-label="Filter by status">
                            <option value="">Status</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Reported</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>In Progress</option>
                            <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Completed</option>
                            <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Pending</option>
                            <option value="6" {{ request('status') === '6' ? 'selected' : '' }}>Reopened</option>
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select name="category" class="form-select cc-auto-filter" aria-label="Filter by category">
                            <option value="">Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}" {{ (string) request('category') === (string) $category->pk ? 'selected' : '' }}>
                                    {{ $category->issue_category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select name="priority" class="form-select cc-auto-filter" aria-label="Filter by priority">
                            <option value="">Priority</option>
                            @foreach($priorities as $p)
                                <option value="{{ $p->pk }}" {{ (string) request('priority') === (string) $p->pk ? 'selected' : '' }}>
                                    {{ $p->priority ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ic-filter-date">
                        <input type="date" name="date_from" class="form-control cc-auto-filter"
                               value="{{ request('date_from') }}" aria-label="From date">
                    </div>
                    <span class="ic-filter-sep">–</span>
                    <div class="ic-filter-date">
                        <input type="date" name="date_to" class="form-control cc-auto-filter"
                               value="{{ request('date_to') }}" aria-label="To date">
                    </div>

                    <button type="button" id="ccResetFilters"
                            class="btn programme-dt-btn-reset {{ $hasFilters ? '' : 'd-none' }}">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="ccBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#ccColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Always-visible search box: datatable-global-ui.js moves DataTables'
                         own filter in here, so typing re-queries the server after a short
                         pause instead of reloading the page on Enter. --}}
                    <div id="ccDtSearch" class="programme-dt-search" data-dt-search-for="centcomIssuesTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: issue_log_management is 65k rows with no secondary
                         indexes, so only one page ever reaches the browser. --}}
                    <table id="centcomIssuesTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">ID No.</th>
                                <th scope="col">Date &amp; Time</th>
                                {{-- Category / Complainant / Nodal / Priority live in joined tables and
                                     issue_log_management has no secondary indexes — sorting on them
                                     measured 110-470ms, so they stay unsortable. --}}
                                <th scope="col">Category</th>
                                <th scope="col">Description</th>
                                <th scope="col">Complainant</th>
                                <th scope="col">Nodal Employee</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates; the global UI fills this in. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="centcomIssuesTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="ccColumnVisibilityModal" tabindex="-1" aria-labelledby="ccColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="ccColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="centcomColumnToggleGrid"></div>
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

    /* ── DataTable (server-side) ──────────────────────────────────────────────
       Search, sort, paging and the footer are DataTables'; the toolbar filters
       ride along on the same ajax call, so nothing here reloads the page.
       `sargamServerOrder` keeps ordering on the server — clicking a header
       re-queries the whole set instead of shuffling the visible page. ── */
    var $table = $('#centcomIssuesTable');

    function filterValue(name) {
        return $('#ccFilters [name="' + name + '"]').val() || '';
    }

    var dt = $table.DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        // 400ms after the last keystroke — search as you type, one query per pause.
        searchDelay: 400,
        order: [[1, 'desc']],
        // A deep link may carry ?search= — seed the box before the first query.
        search: { search: @json($search) },
        ajax: {
            url: '{{ route('admin.issue-management.centcom.data') }}',
            data: function (d) {
                d.status = filterValue('status');
                d.category = filterValue('category');
                d.priority = filterValue('priority');
                d.date_from = filterValue('date_from');
                d.date_to = filterValue('date_to');
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'date', name: 'date' },
            { data: 'category', name: 'category', orderable: false },
            { data: 'description', name: 'description' },
            { data: 'complainant', name: 'complainant', orderable: false },
            { data: 'nodal', name: 'nodal', orderable: false },
            { data: 'priority', name: 'priority', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, className: 'action' }
        ],
        language: {
            emptyTable: '<div class="ic-empty">' +
                '<i class="bi bi-inbox d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Issues Found</h6>' +
                '<p class="mb-0 small">No complaints are assigned to you.</p>' +
                '</div>',
            zeroRecords: '<div class="ic-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Issues Found</h6>' +
                '<p class="mb-0 small">No issue matches the current filters.</p>' +
                '</div>'
        }
    });

    /* ── Toolbar filters: redraw, never reload ────────────────────────────── */
    function anyFilterSet() {
        return ['status', 'category', 'priority', 'date_from', 'date_to']
            .some(function (name) { return filterValue(name) !== ''; }) || dt.search() !== '';
    }

    function syncResetButton() {
        $('#ccResetFilters').toggleClass('d-none', !anyFilterSet());
    }

    $('.cc-auto-filter').on('change', function () {
        dt.page(0).draw();          // back to page 1: the old page may not exist now
        syncResetButton();
        ccUpdateExportCols();
    });

    $('#ccResetFilters').on('click', function () {
        $('#ccFilters .cc-auto-filter').val('');
        dt.search('').page(0).draw();
        syncResetButton();
        ccUpdateExportCols();
    });

    dt.on('search.dt', syncResetButton);

    /* ── Column visibility (DataTables column API) ────────────────────────
       Stored by LABEL, not index — an index points at a different column the
       moment one is added, silently hiding the wrong one. ── */
    var COL_KEY = 'centcomGrid:hiddenColumns:v2';

    /* Header index -> export key (IssueManagementController::exportColumnDefs()).
       Positional: '' marks a column that is not in the export (Action).
       ⚠️ Adding a table column means adding an entry here too. */
    var CC_EXPORT_COLUMN_KEYS = ['id', 'date', 'category', 'description', 'complainant', 'nodal', 'priority', 'status', ''];
    var CC_EXPORT_COL_COUNT = CC_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Every export link carries the columns still on screen AND the filters
       currently applied, so a download matches what the user is looking at. */
    function ccUpdateExportCols() {
        var keys = [];
        dt.columns().every(function () {
            var key = CC_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var filters = {
            search: dt.search() || '',
            status: filterValue('status'),
            category: filterValue('category'),
            priority: filterValue('priority'),
            date_from: filterValue('date_from'),
            date_to: filterValue('date_to')
        };

        ['ccDownloadLink', 'ccExcelLink', 'ccPdfLink', 'ccPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            Object.keys(filters).forEach(function (key) {
                params.delete(key);
                if (filters[key] !== '') { params.set(key, filters[key]); }
            });

            params.delete('cols');
            // Omit ?cols= while nothing is hidden — the server reads that as "all".
            if (keys.length !== CC_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COL_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COL_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    function buildColumnToggles() {
        var $grid = $('#centcomColumnToggleGrid');
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

            var inputId = 'cccolvis_' + index;
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
                ccUpdateExportCols();
            });

            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId)
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });
    }

    buildColumnToggles();
    // Stamp the restored column state onto the export links on first paint too.
    ccUpdateExportCols();
    syncResetButton();
});
</script>
@endpush
