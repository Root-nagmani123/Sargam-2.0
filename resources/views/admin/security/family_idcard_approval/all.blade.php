@extends('admin.layouts.master')
@section('title', 'All Family ID Card Applications')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="All Family ID Card Applications"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">All Family ID Card Applications</h4>
                <a href="{{ route('admin.security.family_idcard_approval.index') }}" class="btn btn-primary">
                    Pending Approvals
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Family Member</th>
                            <th>Employee ID</th>
                            <th>Relation</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                            <tr>
                                <td><code>{{ $app->fml_id_apply }}</code></td>
                                <td><strong>{{ $app->family_name ?? '--' }}</strong></td>
                                <td>{{ $app->emp_id_apply ?? '--' }}</td>
                                <td>{{ $app->family_relation ?? '--' }}</td>
                                <td>
                                    @php
                                        $idStatus = (int) ($app->id_status ?? 1);
                                        $badge = match($idStatus) {
                                            2 => 'bg-success',
                                            3 => 'bg-danger',
                                            default => 'bg-warning text-dark',
                                        };
                                        $statusText = match($idStatus) {
                                            2 => 'Approved',
                                            3 => 'Rejected',
                                            default => 'Pending',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $statusText }}</span>
                                </td>
                                <td>{{ $app->created_date ? $app->created_date->format('d-m-Y H:i') : '--' }}</td>
                                <td>
                                    <a href="{{ route('admin.security.family_idcard_approval.show', encrypt($app->fml_id_apply)) }}"
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No Family ID Card applications found.</td>
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
