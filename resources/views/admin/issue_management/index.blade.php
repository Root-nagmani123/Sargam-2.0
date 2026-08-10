@extends('admin.layouts.master')

@section('title', 'All Issues')

@push('styles')
{{-- Shared Centcom index chrome — the same file the Centcom queue and the master
     grids use, so the whole module stays consistent. See docs/new-design-index-page.md. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // Filters that survive a full page load (deep links / "Raised By You").
    // Everything after first paint is carried on the grid's ajax call instead.
    $exportQuery = array_filter([
        'raised_by' => $raisedBy === 'self' ? 'self' : null,
        'search' => $search,
        'status' => request('status'),
        'category' => request('category'),
        'priority' => request('priority'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="All Requests" :showBack="false">
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
            <a href="{{ route('admin.issue-management.index') }}"
               class="ic-tab {{ $raisedBy === 'all' ? 'is-active' : '' }}"
               @if($raisedBy === 'all') aria-current="page" @endif>All Requests</a>
            <a href="{{ route('admin.issue-management.index', ['raised_by' => 'self']) }}"
               class="ic-tab {{ $raisedBy === 'self' ? 'is-active' : '' }}"
               @if($raisedBy === 'self') aria-current="page" @endif>Raised By You</a>
            <a href="{{ route('admin.issue-management.centcom') }}" class="ic-tab">Assign to you</a>
        </nav>

        <div class="d-flex flex-wrap gap-2">
            {{-- More than one download format → dropdown, per §1 of the doc. --}}
            <div class="dropdown">
                <button type="button" id="imDownloadToggle"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="imDownloadToggle">
                    <li><a class="dropdown-item" id="imDownloadLink"
                           href="{{ route('admin.issue-management.export.list', array_merge(['format' => 'csv'], $exportQuery)) }}">
                            <i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                    <li><a class="dropdown-item" id="imExcelLink"
                           href="{{ route('admin.issue-management.export.excel', $exportQuery) }}">
                            <i class="bi bi-file-earmark-excel me-2" aria-hidden="true"></i>Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item" id="imPdfLink"
                           href="{{ route('admin.issue-management.export.pdf', $exportQuery) }}">
                            <i class="bi bi-file-earmark-pdf me-2" aria-hidden="true"></i>PDF</a></li>
                </ul>
            </div>
            <hr class="my-2">
            <div class="card-body">
                <!-- Filters -->
                {{-- Apply still reloads the page so filters stay deep-linkable; the
                     grid's ajax call reads these same inputs, so both agree. --}}
                <form method="GET" action="{{ route('admin.issue-management.index') }}" class="filter-card p-3 mb-4" id="imFilters">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-3 mb-2">
                            <label class="form-label">Show</label>
                            <select name="raised_by" class="form-select ">
                                <option value="all" {{ request('raised_by', 'all') == 'all' ? 'selected' : '' }}>All issues (raised by me or others)</option>
                                <option value="self" {{ request('raised_by') == 'self' ? 'selected' : '' }}>Raised by me only</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select ">
                                <option value="">All Status</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Reported</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>In Progress</option>
                                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Completed</option>
                                <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Pending</option>
                                <option value="6" {{ request('status') == '6' ? 'selected' : '' }}>Reopened</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select ">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->pk }}" {{ request('category') == $category->pk ? 'selected' : '' }}>
                                        {{ $category->issue_category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-2">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select ">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->pk }}" {{ request('priority') == $priority->pk ? 'selected' : '' }}>
                                        {{ $priority->priority }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control " value="{{ request('date_from') }}">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 mb-2   ">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control " value="{{ request('date_to') }}">
                        </div>
                        <div class="col-12 col-lg-4 d-flex align-items-end gap-2 flex-wrap mb-2">
                            <button type="submit" class="btn btn-primary ">Apply</button>
                            <a href="{{ route('admin.issue-management.index') }}" class="btn btn-outline-secondary " title="Clear filters">Clear Filters</a>
                            @php
                                $exportParams = array_filter([
                                    'search' => request('search'),
                                    'status' => request('status'),
                                    'category' => request('category'),
                                    'priority' => request('priority'),
                                    'date_from' => request('date_from'),
                                    'date_to' => request('date_to'),
                                    'raised_by' => request('raised_by'),
                                ]);
                            @endphp
                            <a href="{{ route('admin.issue-management.export.excel', $exportParams) }}" class="btn btn-success  d-flex align-items-center gap-1" title="Export to Excel">
                                <span class="d-none d-md-inline">Excel</span>
                            </a>
                            <a href="{{ route('admin.issue-management.export.pdf', $exportParams) }}" class="btn btn-danger  d-flex align-items-center gap-1" title="Export to PDF">
                                <span class="d-none d-md-inline">PDF</span>
                            </a>
                        </div>
                    </div>

                    <button type="button" id="imResetFilters"
                            class="btn programme-dt-btn-reset {{ $hasFilters ? '' : 'd-none' }}">Remove Filter</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="imBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#imColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Always-visible search box: datatable-global-ui.js moves DataTables'
                         own filter in here, so typing re-queries the server after a short
                         pause instead of reloading the page on Enter. --}}
                    <div id="imDtSearch" class="programme-dt-search" data-dt-search-for="issueManagementTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: issue_log_management is 65k rows with no secondary
                         indexes, so only one page ever reaches the browser. --}}
                    <table id="issueManagementTable"
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
                        {{-- Rows come from indexData() over ajax (server-side paging),
                             so this stays empty: DataTables fills it per draw. --}}
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates; the global UI fills this in. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="issueManagementTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="imColumnVisibilityModal" tabindex="-1" aria-labelledby="imColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="imColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="issueMgmtColumnToggleGrid"></div>
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
$(document).ready(function () {
    const tableId = '#issueManagementTable';
    if ($.fn.DataTable.isDataTable(tableId)) { return; }

    function filterValue(name) {
        return $('#imFilters [name="' + name + '"]').val() || '';
    }

    /* Server-side: issue_log_management holds 65k+ rows, so the browser fetches
       one page at a time from indexData() instead of receiving the whole set.
       The toolbar filters ride along on the same ajax call. */
    $(tableId).DataTable({
        serverSide: true,
        /* datatable-global-ui.js turns DataTables' native ordering OFF for
           server-side tables unless this opt-in is present, and sorts only the
           rows already loaded instead. We want ORDER BY over the whole set. */
        sargamServerOrder: true,
        processing: true,
        pageLength: 10,
        // No "All": the endpoint caps length, and 65k rows in one draw is not a page.
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        order: [[1, 'desc']],
        searchDelay: 400,
        // A deep link may carry ?search= — seed the box before the first query.
        search: { search: @json(request('search', '')) },
        ajax: {
            url: '{{ route('admin.issue-management.data') }}',
            data: function (d) {
                d.raised_by = filterValue('raised_by');
                d.status = filterValue('status');
                d.category = filterValue('category');
                d.priority = filterValue('priority');
                d.date_from = filterValue('date_from');
                d.date_to = filterValue('date_to');
            }
        },
        /* name= is what the endpoint maps back to a sortable column; only the
           three that exist on issue_log_management itself are orderable, because
           the table carries no secondary indexes. */
        columns: [
            { data: 'id', name: 'id' },
            { data: 'date', name: 'date' },
            { data: 'category', name: 'category', orderable: false },
            { data: 'description', name: 'description', orderable: false },
            { data: 'priority', name: 'priority', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'pe-4' }
        ],
        language: {
            processing: 'Loading…',
            zeroRecords: 'No issues match the current filters',
            emptyTable: 'No issues yet — log a new issue to get started.'
        }
    });
});
</script>
@endpush
