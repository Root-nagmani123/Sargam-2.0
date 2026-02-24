@extends('admin.layouts.master')
@section('title', 'All Duplicate Vehicle Pass Applications')
@section('setup_content')
<div class="container-fluid">
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
                                            'Approved' => 'bg-success',
                                            'Rejected' => 'bg-danger',
                                            default => 'bg-warning text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $app->status_text }}</span>
                                </td>
                                <td>{{ $app->created_date ? $app->created_date->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <a href="{{ route('admin.security.vehicle_pass_approval.show', encrypt('dup-' . $app->vehicle_tw_pk)) }}"
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
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
