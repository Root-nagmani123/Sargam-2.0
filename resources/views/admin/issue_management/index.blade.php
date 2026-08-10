@extends('admin.layouts.master')

@section('title', 'All Issues - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid issue-management-index">
    <x-breadcrum title="All Issues" />
    <div class="datatables">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h1 class="h4 fw-semibold mb-1">Issue Management - All Issues</h1>
                </div>
                <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary  d-flex align-items-center gap-2 shadow-sm">
                    <i class="material-icons material-symbols-rounded">add</i>
                    Add New Issue
                </a>
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
                </form>

                <!-- Issues DataTable -->
              <!-- Issues Table -->
              <div class="table-responsive">
                    <table class="table mb-0" id="issueManagementTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Priority</th>
                               
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        {{-- Rows come from indexData() over ajax (server-side paging),
                             so this stays empty: DataTables fills it per draw. --}}
                        <tbody></tbody>
                    </table>
                </div>
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
