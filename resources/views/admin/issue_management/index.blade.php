@extends('admin.layouts.master')

@section('title', 'All Issues - Sargam | Lal Bahadur')

@section('css')
<style>
    .issue-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075), 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .issue-card .card-header {
        background: linear-gradient(135deg, #af2910 0%, #f25d33 100%);
        color: #fff;
        border: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }
    .issue-table {
        --bs-table-hover-bg: rgba(0, 74, 147, 0.04);
        --bs-table-hover-color: inherit;
    }
    .issue-table thead th {
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
        color: #af2910;
        background: rgba(175, 41, 16, 0.06);
    }
    .nav-tabs-issue .nav-link.active {
        color: #af2910;
        background: rgba(175, 41, 16, 0.1);
    }
    .empty-state {
        padding: 3rem 1.5rem;
        text-align: center;
    }
    .empty-state-icon {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1rem;
        background: rgba(0, 74, 147, 0.08);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #004a93;
    }
    @media (max-width: 768px) {
        .issue-table thead th { font-size: 0.75rem; }
    }
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="All Issues" />
    <div class="datatables">
        <div class="card issue-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-25" style="width: 2.5rem; height: 2.5rem;">
                        <iconify-icon icon="solar:clipboard-list-bold-duotone" class="fs-5"></iconify-icon>
                    </span>
                    <span>Issue Management - All Issues</span>
                </div>
                <a href="{{ route('admin.issue-management.create') }}" class="btn btn-light btn-sm d-flex align-items-center gap-2 shadow-sm">
                    <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
                    Log New Issue
                </a>
            </div>
            <div class="card-body p-0">
                <!-- Active / Archive Tabs -->
                @php
                    $currentTab = request('tab', 'active');
                    $queryParams = request()->except('tab', 'page');
                    $activeTabUrl = route('admin.issue-management.index', array_merge($queryParams, ['tab' => 'active']));
                    $archiveTabUrl = route('admin.issue-management.index', array_merge($queryParams, ['tab' => 'archive']));
                @endphp
              

                <!-- Filters -->
                <div class="p-4 pb-0">
                    <form method="GET" action="{{ route('admin.issue-management.index') }}" class="filter-card p-3 mb-4">
                        <input type="hidden" name="tab" value="{{ $currentTab }}">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <iconify-icon icon="solar:filter-bold-duotone" class="text-primary"></iconify-icon>
                            <span class="fw-semibold small text-body-secondary">Filters</span>
                        </div>
                        <div class="row g-3">
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
                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small fw-medium text-body-secondary">Date From</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small fw-medium text-body-secondary">Date To</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-12 col-lg-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                                    <iconify-icon icon="solar:magnifer-bold"></iconify-icon>
                                    Apply
                                </button>
                                <a href="{{ route('admin.issue-management.index', ['tab' => $currentTab]) }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                                    <iconify-icon icon="solar:refresh-bold"></iconify-icon>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

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
                                        <h6 class="text-body-secondary mb-1">No {{ $currentTab === 'archive' ? 'archived' : 'active' }} issues</h6>
                                        <p class="small text-body-secondary mb-0">
                                            @if($currentTab === 'archive')
                                                Completed issues will appear here.
                                            @else
                                                Try adjusting your filters or log a new issue.
                                            @endif
                                        </p>
                                        @if($currentTab === 'active')
                                        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary btn-sm mt-3">Log New Issue</a>
                                        @else
                                        <a href="{{ route('admin.issue-management.index', ['tab' => 'active']) }}" class="btn btn-outline-primary btn-sm mt-3">View Active Issues</a>
                                        @endif
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
