@extends('admin.layouts.master')

@section('title', 'All Issues - Sargam | Lal Bahadur')

<<<<<<< HEAD
=======
@section('css')
<style>
    .issue-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075), 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .issue-card .card-header {
        background: linear-gradient(135deg, #004a93 0%, #f25d33 100%);
        color: #fff;
        border: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }
    .issue-table {
        table-layout: fixed;
        width: 100%;
        --bs-table-hover-bg: rgba(0, 74, 147, 0.04);
        --bs-table-hover-color: inherit;
    }
    .issue-table thead th {
        width: 1%;
        font-weight: 600;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #495057;
        border-bottom-width: 2px;
        white-space: nowrap;
    }
    .issue-table tbody td {
        vertical-align: middle;
    }
    .badge-pill {
        padding: 0.35em 0.65em;
        font-weight: 500;
        font-size: 0.75rem;
    }
    .btn-action {
        width: 2.25rem;
        height: 2.25rem;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }
    .filter-card {
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 1px solid rgba(0, 0, 0, 0.06);
    }
    .nav-tabs-issue .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        transition: color 0.2s, background 0.2s;
    }
    .nav-tabs-issue .nav-link:hover {
        color: #004a93;
        background: rgba(175, 41, 16, 0.06);
    }
    .nav-tabs-issue .nav-link.active {
        color: #004a93;
        background: rgba(175, 41, 16, 0.1);
    }
    @media (max-width: 768px) {
        .issue-table thead th { font-size: 0.75rem; }
    }
</style>
@endsection

>>>>>>> 1a2c46f4 (estate datatable)
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="All Issues" />
    <div class="datatables">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h1 class="h4 fw-semibold mb-1">Issue Management - All Issues</h1>
                </div>
                <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2 shadow-sm">
                    <i class="material-icons material-symbols-rounded">add</i>
                    Add New Issue
                </a>
            </div>
            <hr class="my-2">
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" action="{{ route('admin.issue-management.index') }}" class="filter-card p-3 mb-4">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small fw-medium text-body-secondary">Show</label>
                            <select name="raised_by" class="form-select form-select-sm">
                                <option value="all" {{ request('raised_by', 'all') == 'all' ? 'selected' : '' }}>All issues (raised by me or others)</option>
                                <option value="self" {{ request('raised_by') == 'self' ? 'selected' : '' }}>Raised by me only</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small fw-medium text-body-secondary">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Reported</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>In Progress</option>
                                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Completed</option>
                                <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Pending</option>
                                <option value="6" {{ request('status') == '6' ? 'selected' : '' }}>Reopened</option>
                            </select>
                        </div>
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 9b058492 (estate datatable)
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small fw-medium text-body-secondary">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->pk }}" {{ request('category') == $category->pk ? 'selected' : '' }}>
                                        {{ $category->issue_category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small fw-medium text-body-secondary">Priority</label>
                            <select name="priority" class="form-select form-select-sm">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->pk }}" {{ request('priority') == $priority->pk ? 'selected' : '' }}>
                                        {{ $priority->priority }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small fw-medium text-body-secondary">Date From</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label small fw-medium text-body-secondary">Date To</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-12 col-lg-4 d-flex align-items-end gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                            <a href="{{ route('admin.issue-management.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">Clear Filters</a>
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
                            <a href="{{ route('admin.issue-management.export.excel', $exportParams) }}" class="btn btn-success btn-sm d-flex align-items-center gap-1" title="Export to Excel">
                                <span class="d-none d-md-inline">Excel</span>
                            </a>
                            <a href="{{ route('admin.issue-management.export.pdf', $exportParams) }}" class="btn btn-danger btn-sm d-flex align-items-center gap-1" title="Export to PDF">
                                <span class="d-none d-md-inline">PDF</span>
                            </a>
                        </div>
                    </div>
                </form>
<<<<<<< HEAD

                <!-- Issues DataTable -->
<<<<<<< HEAD
              <!-- Issues Table -->
              <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Priority</th>
                               
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issues as $issue)
                            <tr>
                                <td class="ps-4 fw-medium text-body-secondary">#{{ $issue->pk }}</td>
                                <td><span class="text-body-secondary">{{ $issue->created_date->format('d M Y') }}</span></td>
                                <td>{{ $issue->category->issue_category ?? '—' }}</td>
                                <td class="text-break" style="max-width: 220px;">{{ Str::limit($issue->description, 50) }}</td>
                                <td>
                                    @php
                                        $p = $issue->priority->priority ?? 'N/A';
                                        $priorityClass = $p == 'High' ? 'danger' : ($p == 'Medium' ? 'warning' : 'info');
                                    @endphp
                                    <span class="badge badge-pill bg-{{ $priorityClass }} {{ $priorityClass == 'warning' ? 'text-dark' : '' }}">{{ $p }}</span>
                                </td>
                               
                                <td>
                                    @php
                                        $s = (int) $issue->issue_status;
                                        $statusClass = $s == 2 ? 'success' : ($s == 1 ? 'info' : ($s == 6 ? 'warning' : 'secondary'));
                                    @endphp
                                    <span class="badge badge-pill bg-{{ $statusClass }} {{ $statusClass == 'warning' ? 'text-dark' : '' }}">{{ $issue->status_label }}</span>
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="btn btn-action btn-info btn-sm" title="View">
                                            <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                        </a>
                                        @if($issue->issue_logger == Auth::user()->user_id || $issue->created_by == Auth::user()->user_id)
                                        <a href="{{ route('admin.issue-management.edit', $issue->pk) }}" class="btn btn-action btn-warning btn-sm" title="Edit">
                                            <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="p-0">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <iconify-icon icon="solar:clipboard-list-bold-duotone" class="fs-1"></iconify-icon>
                                        </div>
                                        <h6 class="text-body-secondary mb-1">No issues</h6>
                                        <p class="small text-body-secondary mb-0">Try adjusting your filters or log a new issue.</p>
                                        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary btn-sm mt-3">Log New Issue</a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
=======
                <div class="table-responsive">
                    {!! $dataTable->table() !!}
>>>>>>> 1a2c46f4 (estate datatable)
=======
                    </form>
>>>>>>> 0623079b (Revert "estate datatable")
                </div>
=======
>>>>>>> 9b058492 (estate datatable)

                <!-- Issues DataTable -->
                <div class="table-responsive">
                    {!! $dataTable->table() !!}
                </div>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD
@endsection
<<<<<<< HEAD
=======
@endsection
<<<<<<< HEAD
=======
>>>>>>> 9b058492 (estate datatable)

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
<<<<<<< HEAD
>>>>>>> 1a2c46f4 (estate datatable)
=======
>>>>>>> 0623079b (Revert "estate datatable")
=======
>>>>>>> 9b058492 (estate datatable)
