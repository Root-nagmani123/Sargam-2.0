@extends('admin.layouts.master')

@section('title', 'All Issues - Sargam | Lal Bahadur')

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
    <x-breadcrum title="All Issues" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">Issue Management - All Issues</h4>
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
                    <form method="GET" action="{{ route('admin.issue-management.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Reported</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>In Progress</option>
                                    <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Completed</option>
                                    <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Pending</option>
                                    <option value="6" {{ request('status') == '6' ? 'selected' : '' }}>Reopened</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->pk }}" {{ request('category') == $category->pk ? 'selected' : '' }}>
                                            {{ $category->issue_category }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                                    <th>Priority</th>
                                    <th>Behalf</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($issues as $issue)
                                <tr>
                                    <td>{{ $issue->pk }}</td>
                                    <td>{{ $issue->created_date->format('d-m-Y') }}</td>
                                    <td>{{ $issue->category->issue_category ?? 'N/A' }}</td>
                                    <td>{{ Str::limit($issue->description, 50) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $issue->priority->priority == 'High' ? 'danger' : ($issue->priority->priority == 'Medium' ? 'warning' : 'info') }}">
                                            {{ $issue->priority->priority ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $issue->behalf == 0 ? 'primary' : 'secondary' }}">
                                            {{ $issue->behalf_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $issue->issue_status == 2 ? 'success' : ($issue->issue_status == 1 ? 'info' : 'warning') }}">
                                            {{ $issue->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="btn btn-sm btn-info">
                                            <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                        </a>
                                        <a href="{{ route('admin.issue-management.edit', $issue->pk) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No issues found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $issues->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
