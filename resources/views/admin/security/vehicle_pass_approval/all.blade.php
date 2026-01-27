@extends('admin.layouts.master')
@section('title', 'All Vehicle Pass Applications - Security Management')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'All Vehicle Pass Applications']) 
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">All Vehicle Pass Applications</h4>
                <a href="{{ route('admin.security.vehicle_pass.create') }}" class="btn btn-primary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Apply for New Pass
                </a>
            </div>

            <div class="alert alert-info">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">info</i>
                This page displays all vehicle pass applications regardless of status.
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover mb-0" id="allApplicationsTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th style="width:140px;">Request ID</th>
                            <th>Employee</th>
                            <th>Vehicle Type</th>
                            <th style="width:140px;">Reg. Number</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:110px;">Forward</th>
                            <th style="width:120px;">Created Date</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $index => $app)
                            <tr>
                                <td>{{ $applications->firstItem() + $index }}</td>
                                <td>
                                    <code class="bg-light text-dark p-1">{{ $app->veh_req_id }}</code>
                                </td>
                                <td>
                                    @if($app->employee)
                                        <strong>{{ $app->employee->emp_name }}</strong><br>
                                        <small class="text-muted">{{ $app->employee->emp_code ?? 'N/A' }}</small>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($app->vehicleType)
                                        {{ $app->vehicleType->vehicle_type }}
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $app->veh_reg_no }}</strong>
                                </td>
                                <td>
                                    @php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch($app->vech_card_status) {
                                            case 1:
                                                $statusClass = 'warning';
                                                $statusText = 'Pending';
                                                break;
                                            case 2:
                                                $statusClass = 'success';
                                                $statusText = 'Approved';
                                                break;
                                            case 3:
                                                $statusClass = 'danger';
                                                $statusText = 'Rejected';
                                                break;
                                            default:
                                                $statusClass = 'secondary';
                                                $statusText = 'Unknown';
                                        }
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td>
                                    @php
                                        $forwardClass = '';
                                        $forwardText = '';
                                        switch($app->veh_card_forward_status) {
                                            case 0:
                                                $forwardClass = 'secondary';
                                                $forwardText = 'Not Sent';
                                                break;
                                            case 1:
                                                $forwardClass = 'info';
                                                $forwardText = 'Forwarded';
                                                break;
                                            case 2:
                                                $forwardClass = 'success';
                                                $forwardText = 'Card Ready';
                                                break;
                                            default:
                                                $forwardClass = 'secondary';
                                                $forwardText = '--';
                                        }
                                    @endphp
                                    <span class="badge bg-{{ $forwardClass }}">{{ $forwardText }}</span>
                                </td>
                                <td>
                                    <small>{{ $app->created_date ? $app->created_date->format('d-M-Y') : '--' }}</small>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.security.vehicle_pass.show', encrypt($app->vehicle_tw_pk)) }}" class="text-primary" title="View Details">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i>
                                        </a>
                                        @if($app->vech_card_status == 1)
                                            <a href="{{ route('admin.security.vehicle_pass.edit', encrypt($app->vehicle_tw_pk)) }}" class="text-success" title="Edit">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No applications found.</td>
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

@push('scripts')
<script>
$(document).ready(function() {
    // You can add DataTables initialization here if needed
    // $('#allApplicationsTable').DataTable({
    //     "pageLength": 15,
    //     "order": [[7, 'desc']]
    // });
});
</script>
@endpush
