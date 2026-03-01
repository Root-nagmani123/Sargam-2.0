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
    <x-breadcrum title="CENTCOM - Reported Complaints" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">CENTCOM - Issues Assigned To You</h4>
                    </div>
                    <div class="col-6 text-end">
                        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary">
                            <iconify-icon icon="ep:circle-plus-filled"></iconify-icon> Log New Issue
                        </a>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.issue-management.centcom') }}" class="mb-4 p-3 rounded border bg-light">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <iconify-icon icon="solar:filter-bold-duotone" class="text-primary"></iconify-icon>
                            <span class="fw-semibold small">Filters</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small fw-medium">Search</label>
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="ID, description, category..." value="{{ request('search') }}">
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
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-12 col-md-4 col-lg-2">
                                <label class="form-label small fw-medium">Date To</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
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
                        <table class="table">
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
                            <tbody>
                                @forelse($issues as $issue)
                                <tr>
                                    <td>{{ $issue->pk }}</td>
                                    <td>{{ $issue->created_date->format('d-m-Y H:i') }}</td>
                                    <td>{{ $issue->category->issue_category ?? 'N/A' }}</td>
                                    <td>{{ Str::limit($issue->description, 60) }}</td>
                                    
                            
                                    <td>
                                        <span class="badge bg-{{ $issue->issue_status == 2 ? 'success' : ($issue->issue_status == 1 ? 'info' : ($issue->issue_status == 6 ? 'warning' : 'secondary')) }}">
                                            {{ $issue->status_label }}
                                        </span>
                                    </td>
                                  
                                    <td>
                                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="btn btn-sm btn-info" title="View Details">
                                            <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No complaints assigned to you</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $issues->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
