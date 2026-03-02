@extends('admin.layouts.master')

@section('title', 'All Issues - Sargam | Lal Bahadur')

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

                <!-- Issues DataTable -->
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
                </div>

                <!-- Pagination -->
                @if($issues->hasPages())
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-4 py-3 border-top bg-light">
                    <small class="text-body-secondary">
                        Showing {{ $issues->firstItem() ?? 0 }} - {{ $issues->lastItem() ?? 0 }} of {{ $issues->total() }}
                    </small>
                    <nav aria-label="Issue pagination">
                        {{ $issues->withQueryString()->links() }}
                    </nav>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection