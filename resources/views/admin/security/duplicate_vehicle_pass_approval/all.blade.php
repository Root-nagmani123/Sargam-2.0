@extends('admin.layouts.master')
@section('title', 'All Duplicate Vehicle Pass Applications')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* Canonical country/index look (new-design-index-page.md §3b) — scoped to .duplicate-vehicle-pass-approval-page. */
    .duplicate-vehicle-pass-approval-page .status-pill { padding: .4em .85em; font-weight: 600; }
    .duplicate-vehicle-pass-approval-page .status-pill.bg-success-subtle   { color: #146c43; }
    .duplicate-vehicle-pass-approval-page .status-pill.bg-danger-subtle    { color: #b02a37; }
    .duplicate-vehicle-pass-approval-page .status-pill.bg-warning-subtle   { color: #b54708; }
    .duplicate-vehicle-pass-approval-page .status-pill.bg-secondary-subtle { color: #475467; }

    .duplicate-vehicle-pass-approval-page .dva-act {
        display: inline-flex; flex-direction: column; align-items: center; gap: 2px;
        font-size: .72rem; font-weight: 500; line-height: 1;
        text-decoration: none; background: transparent; border: 0; padding: 0;
    }
    .duplicate-vehicle-pass-approval-page .dva-act i { font-size: 1.1rem; }
    .duplicate-vehicle-pass-approval-page .dva-act--view    { color: #475467; }
    .duplicate-vehicle-pass-approval-page .dva-act--approve { color: #146c43; }
    .duplicate-vehicle-pass-approval-page .dva-act--reject  { color: var(--bs-danger, #dc3545); }
</style>
@endpush

@section('setup_content')
<div class="container-fluid duplicate-vehicle-pass-approval-page">
    <x-breadcrum title="All Duplicate Vehicle Pass Applications"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">All Duplicate Vehicle Pass Applications</h4>
                <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="btn btn-primary">
                    Pending Approvals
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Employee</th>
                            <th>Vehicle Type</th>
                            <th>Vehicle No</th>
                            <th>Original Pass No</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                            <tr>
                                <td><code>{{ $app->vehicle_tw_pk }}</code></td>
                                <td>{{ $app->employee_name ?? '--' }}</td>
                                <td>{{ $app->vehicleType->vehicle_type ?? '--' }}</td>
                                <td>{{ $app->vehicle_no }}</td>
                                <td>{{ $app->vehicle_pass_no ?? '--' }}</td>
                                <td>
                                    @php
                                        $badge = match($app->status_text) {
                                            'Approved' => 'bg-success-subtle',
                                            'Rejected' => 'bg-danger-subtle',
                                            default => 'bg-warning-subtle',
                                        };
                                    @endphp
                                    <span class="status-pill badge {{ $badge }}">{{ $app->status_text }}</span>
                                </td>
                                <td>{{ $app->created_date ? $app->created_date->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <a href="{{ route('admin.security.vehicle_pass_approval.show', encrypt('dup-' . $app->vehicle_tw_pk)) }}"
                                       class="dva-act dva-act--view" title="View" aria-label="View details">
                                        <i class="bi bi-eye" aria-hidden="true"></i><span>View</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No applications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
