@extends('admin.layouts.master')

@section('title', 'CENTCOM Complaints - Sargam | Lal Bahadur')

@section('css')
<style>
.table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="CENTCOM - Issues Assigned To You" />
    <div class="datatables">
        <div class="card" >
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">CENTCOM - Issues Assigned To You</h4>
                    </div>
                    <div class="col-6 text-end">
                        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Log New Issue
                        </a>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <!-- Filters -->
                    {{-- Apply still reloads the page so filters stay deep-linkable; the grid's
                         ajax call reads these same inputs, so both agree. --}}
                    <form method="GET" action="{{ route('admin.issue-management.centcom') }}" class="mb-4 p-3 rounded border bg-light" id="ccFilters">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <iconify-icon icon="solar:filter-bold-duotone" class="text-primary"></iconify-icon>
                            <span class="fw-semibold small">Filters</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small fw-medium">Search</label>
                                <input type="text" name="search" class="form-control " placeholder="ID, description, category..." value="{{ request('search') }}">
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Reported</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>In Progress</option>
                                    <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Completed</option>
                                    <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Pending</option>
                                    <option value="6" {{ request('status') == '6' ? 'selected' : '' }}>Reopened</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Category</label>
                                <select name="category" class="form-select form-select-sm">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->pk }}" {{ request('category') == $category->pk ? 'selected' : '' }}>
                                            {{ $category->issue_category }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Priority</label>
                                <select name="priority" class="form-select form-select-sm">
                                    <option value="">All Priorities</option>
                                    @foreach($priorities as $p)
                                        <option value="{{ $p->pk }}" {{ request('priority') == $p->pk ? 'selected' : '' }}>{{ $p->priority ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Date From</label>
                                <input type="date" name="date_from" class="form-control " value="{{ request('date_from') }}">
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Date To</label>
                                <input type="date" name="date_to" class="form-control " value="{{ request('date_to') }}">
                            </div>
                            <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <iconify-icon icon="solar:magnifer-bold"></iconify-icon> Filter
                                </button>
                                <a href="{{ route('admin.issue-management.centcom') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                            </div>
                        </div>
                    </form>

                    <!-- Issues Table -->
                    <div class="table-responsive datatables">
                        <table class="table" id="centcomIssuesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            {{-- Rows come from centcomData() over ajax (server-side
                                 paging), so this stays empty. --}}
                            <tbody></tbody>
                        </table>
                    </div>

                    {{-- No server pager: the DataTable pages this grid from the
                         server one draw at a time. --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    const tableId = '#centcomIssuesTable';
    if ($.fn.DataTable.isDataTable(tableId)) { return; }

    function filterValue(name) {
        return $('#ccFilters [name="' + name + '"]').val() || '';
    }

    /* Server-side: this scope can still run to thousands of issues for a busy
       assignee, so the browser fetches one page at a time from centcomData().
       The toolbar filters ride along on the same ajax call. */
    $(tableId).DataTable({
        serverSide: true,
        /* datatable-global-ui.js turns DataTables' native ordering OFF for
           server-side tables unless this opt-in is present, and sorts only the
           rows already loaded instead. We want ORDER BY over the whole set. */
        sargamServerOrder: true,
        processing: true,
        pageLength: 10,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        order: [[1, 'desc']],
        searchDelay: 400,
        // The filter card's own Search box reloads the page; seed the grid from it.
        search: { search: @json(request('search', '')) },
        ajax: {
            url: '{{ route('admin.issue-management.centcom.data') }}',
            data: function (d) {
                // NB: never set d.search here — DataTables sends search[value]
                // as an object and a string would clobber it.
                d.status = filterValue('status');
                d.category = filterValue('category');
                d.priority = filterValue('priority');
                d.date_from = filterValue('date_from');
                d.date_to = filterValue('date_to');
            }
        },
        /* name= is what the endpoint maps back to a sortable column; only the
           columns that exist on issue_log_management itself are orderable,
           because the table carries no secondary indexes. */
        columns: [
            { data: 'id', name: 'id' },
            { data: 'date', name: 'date' },
            { data: 'category', name: 'category', orderable: false },
            { data: 'description', name: 'description', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            processing: 'Loading…',
            zeroRecords: 'No complaints match the current filters',
            emptyTable: 'No complaints assigned to you'
        }
    });
});
</script>
@endsection
