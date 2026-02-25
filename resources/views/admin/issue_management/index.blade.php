@extends('admin.layouts.master')

@section('title', 'All Issues - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid issue-management-index">
    <x-breadcrum title="All Issues" />
    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-1 overflow-hidden">
            <div class="card-header bg-body-tertiary border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded-1 bg-primary bg-opacity-10 p-2 text-primary">
                        <i class="material-icons material-symbols-rounded fs-5 mb-0">assignment</i>
                    </span>
                    <h1 class="h5 fw-bold mb-0 text-body">Issue Management</h1>
                </div>
                <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 rounded-1 px-3 shadow-sm">
                    <i class="material-icons material-symbols-rounded" style="font-size:1.1rem;">add</i>
                    <span>Add New Issue</span>
                </a>
            </div>
            <div class="card-body p-4">
                <!-- Filters -->
                <form method="GET" action="{{ route('admin.issue-management.index') }}" class="rounded-1 border bg-body-secondary bg-opacity-50 p-4 mb-4">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-body-secondary mb-1">Show</label>
                            <select name="raised_by" class="form-select form-select-sm rounded-1">
                                <option value="all" {{ request('raised_by', 'all') == 'all' ? 'selected' : '' }}>All issues (raised by me or others)</option>
                                <option value="self" {{ request('raised_by') == 'self' ? 'selected' : '' }}>Raised by me only</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-body-secondary mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm rounded-1">
                                <option value="">All Status</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Reported</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>In Progress</option>
                                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Completed</option>
                                <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Pending</option>
                                <option value="6" {{ request('status') == '6' ? 'selected' : '' }}>Reopened</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-body-secondary mb-1">Category</label>
                            <select name="category" class="form-select form-select-sm rounded-1">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->pk }}" {{ request('category') == $category->pk ? 'selected' : '' }}>
                                        {{ $category->issue_category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-body-secondary mb-1">Priority</label>
                            <select name="priority" class="form-select form-select-sm rounded-1">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->pk }}" {{ request('priority') == $priority->pk ? 'selected' : '' }}>
                                        {{ $priority->priority }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-body-secondary mb-1">Date From</label>
                            <input type="date" name="date_from" class="form-control form-control-sm rounded-1" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small fw-semibold text-body-secondary mb-1">Date To</label>
                            <input type="date" name="date_to" class="form-control form-control-sm rounded-1" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-12 col-lg-6 d-flex flex-wrap align-items-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm rounded-1 px-3 d-inline-flex align-items-center gap-1">
                                <i class="material-icons material-symbols-rounded" style="font-size:1rem;">filter_list</i>
                                Apply
                            </button>
                            <a href="{{ route('admin.issue-management.index') }}" class="btn btn-outline-secondary btn-sm rounded-1 px-3" title="Clear filters">
                                Clear
                            </a>
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
                            <div class="btn-group btn-group-sm gap-2" role="group">
                                <a href="{{ route('admin.issue-management.export.excel', $exportParams) }}" class="btn btn-success rounded-1 d-inline-flex align-items-center gap-1" title="Export to Excel">
                                    <i class="material-icons material-symbols-rounded" style="font-size:1rem;">table_chart</i>
                                    <span class="d-none d-md-inline">Excel</span>
                                </a>
                                <a href="{{ route('admin.issue-management.export.pdf', $exportParams) }}" class="btn btn-danger rounded-1 d-inline-flex align-items-center gap-1" title="Export to PDF">
                                    <i class="material-icons material-symbols-rounded" style="font-size:1rem;">picture_as_pdf</i>
                                    <span class="d-none d-md-inline">PDF</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Issues DataTable -->
                <div class="table-responsive">
                    <table class="table text-nowrap align-middle mb-0" id="issuesTable">
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
                        <tbody>
                            @forelse($issues as $issue)
                            <tr>
                                <td>#{{ $issue->pk }}</td>
                                <td>{{ $issue->created_date->format('d M Y') }}</td>
                                <td>{{ $issue->category->issue_category ?? '—' }}</td>
                                <td>{{ Str::limit($issue->description, 50) }}</td>
                                <td>
                                    @php
                                        $p = $issue->priority->priority ?? 'N/A';
                                        $priorityClass = $p == 'High' ? 'danger' : ($p == 'Medium' ? 'warning' : 'info');
                                    @endphp
                                    <span class="badge rounded-1 bg-{{ $priorityClass }} {{ $priorityClass == 'warning' ? 'text-dark' : '' }}">{{ $p }}</span>
                                </td>
                                <td>
                                    @php
                                        $s = (int) $issue->issue_status;
                                        $statusClass = $s == 2 ? 'success' : ($s == 1 ? 'info' : ($s == 6 ? 'warning' : 'secondary'));
                                    @endphp
                                    <span class="badge rounded-1 bg-{{ $statusClass }} {{ $statusClass == 'warning' ? 'text-dark' : '' }}">{{ $issue->status_label }}</span>
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="text-primary" title="View">
                                            <i class="material-icons material-symbols-rounded">visibility</i>
                                        </a>
                                        @if($issue->issue_logger == Auth::user()->user_id || $issue->created_by == Auth::user()->user_id)
                                        <a href="{{ route('admin.issue-management.edit', $issue->pk) }}" class="text-primary" title="Edit">
                                            <i class="material-icons material-symbols-rounded">edit</i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <div class="text-center py-5 px-3">
                                        <div class="rounded-circle bg-body-secondary bg-opacity-50 d-inline-flex p-4 mb-3">
                                            <iconify-icon icon="solar:clipboard-list-bold-duotone" class="fs-1 text-body-secondary"></iconify-icon>
                                        </div>
                                        <h6 class="fw-semibold text-body mb-2">No issues found</h6>
                                        <p class="small text-body-secondary mb-0 mx-auto" style="max-width:320px;">Try adjusting your filters or log a new issue to get started.</p>
                                        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary btn-sm rounded-1 mt-3 px-3">
                                            Log New Issue
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable) return;
        var $table = $('#issuesTable');
        if (!$table.length) return;
        // Only init if table has data rows (not just empty-state row with colspan)
        var hasDataRows = $table.find('tbody tr').filter(function() { return $(this).find('td[colspan]').length === 0; }).length > 0;
        if (!hasDataRows) return;
        if ($.fn.DataTable.isDataTable($table)) return;
        $table.DataTable({
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            responsive: true,
            language: {
                search: 'Search issues:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ issues',
                infoEmpty: 'No issues',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching issues found',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            },
            drawCallback: function() {
                if (typeof window.adjustAllDataTables === 'function') {
                    try { window.adjustAllDataTables(); } catch (e) {}
                }
            }
        });
    });
})();
</script>
@endpush