@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Application - Approval Details')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Vehicle Pass Application - Approval Review</h4>
                    <small class="text-muted">Request ID: <code>{{ $application->vehicle_req_id }}</code></small>
                </div>
                <div>
                    <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                        Back to Pending
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Application Status -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert" style="background-color: #f0f7ff; border-left: 4px solid #0066cc;">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Status:</strong><br>
                                @php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch($application->vech_card_status) {
                                        case 1:
                                            $statusClass = 'warning';
                                            $statusText = 'Pending Approval';
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
                            </div>
                            <div class="col-md-4">
                                <strong>Submitted Date:</strong><br>
                                {{ $application->created_date ? $application->created_date->format('d-M-Y H:i') : '--' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Last Updated:</strong><br>
                                {{ $application->modified_date ? $application->modified_date->format('d-M-Y H:i') : 'Not updated' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Details -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">two_wheeler</i>
                        Vehicle Details
                    </h5>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Type</label>
                        <div class="form-control bg-light">
                            @if($application->vehicleType)
                                {{ $application->vehicleType->vehicle_type }}
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Number</label>
                        <div class="form-control bg-light">
                            <strong>{{ $application->vehicle_no }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valid From</label>
                        <div class="form-control bg-light">
                            {{ $application->veh_card_valid_from ? \Carbon\Carbon::parse($application->veh_card_valid_from)->format('d-M-Y') : '--' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valid To</label>
                        <div class="form-control bg-light">
                            {{ $application->vech_card_valid_to ? \Carbon\Carbon::parse($application->vech_card_valid_to)->format('d-M-Y') : '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Government Vehicle</label>
                        <div class="form-control bg-light">
                            {!! $application->gov_veh == 1 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Employee ID Card</label>
                        <div class="form-control bg-light">
                            {{ $application->employee_id_card ?? '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee Details -->
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">person</i>
                        Employee Details
                    </h5>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Employee Name</label>
                        <div class="form-control bg-light">
                            @if($application->employee)
                                {{ $application->employee->emp_name }}
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Employee Code</label>
                        <div class="form-control bg-light">
                            @if($application->employee)
                                {{ $application->employee->emp_code ?? '--' }}
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachment -->
            @if($application->doc_upload)
                <div class="row mb-3 mt-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">attach_file</i>
                            Attached Document
                        </h5>
                        <a href="{{ Storage::url($application->doc_upload) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">download</i>
                            Download Document
                        </a>
                    </div>
                </div>
            @endif

            <!-- Approval History -->
            @if($application->approvals && count($application->approvals) > 0)
                <div class="row mb-3 mt-4">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">history</i>
                            Approval History
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Recommended</th>
                                        <th>Approved By</th>
                                        <th>Remarks</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($application->approvals as $approval)
                                        <tr>
                                            <td>
                                                @php
                                                    $approvalStatusClass = '';
                                                    $approvalStatusText = '';
                                                    switch($approval->status ?? 0) {
                                                        case 0:
                                                            $approvalStatusClass = 'warning';
                                                            $approvalStatusText = 'Pending';
                                                            break;
                                                        case 1:
                                                            $approvalStatusClass = 'info';
                                                            $approvalStatusText = 'Forwarded';
                                                            break;
                                                        case 2:
                                                            $approvalStatusClass = 'success';
                                                            $approvalStatusText = 'Approved';
                                                            break;
                                                        case 3:
                                                            $approvalStatusClass = 'danger';
                                                            $approvalStatusText = 'Rejected';
                                                            break;
                                                        default:
                                                            $approvalStatusClass = 'secondary';
                                                            $approvalStatusText = '--';
                                                    }
                                                @endphp
                                                <span class="badge bg-{{ $approvalStatusClass }}">{{ $approvalStatusText }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $recommendClass = '';
                                                    $recommendText = '';
                                                    switch($approval->veh_recommend_status ?? 0) {
                                                        case 1:
                                                            $recommendClass = 'success';
                                                            $recommendText = 'Recommended';
                                                            break;
                                                        case 2:
                                                            $recommendClass = 'success';
                                                            $recommendText = 'Approved';
                                                            break;
                                                        case 3:
                                                            $recommendClass = 'danger';
                                                            $recommendText = 'Not Recommended';
                                                            break;
                                                        default:
                                                            $recommendClass = 'secondary';
                                                            $recommendText = '--';
                                                    }
                                                @endphp
                                                <span class="badge bg-{{ $recommendClass }}">{{ $recommendText }}</span>
                                            </td>
                                            <td>
                                                @if($approval->approvedBy)
                                                    {{ $approval->approvedBy->emp_name ?? 'N/A' }}
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $approval->veh_approval_remarks ?? '--' }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $approval->modified_date ? $approval->modified_date->format('d-M-Y H:i') : '--' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Approval Actions -->
            @if($application->vech_card_status == 1)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">check_circle</i>
                            Approval Actions
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <form action="{{ route('admin.security.vehicle_pass_approval.approve', encrypt($application->vehicle_tw_pk)) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="forward_status_approve" class="form-label">Forward Status</label>
                                        <select name="forward_status" id="forward_status_approve" class="form-select">
                                            <option value="1">Forwarded</option>
                                            <option value="2">Card Ready</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="remarks_approve" class="form-label">Remarks (Optional)</label>
                                        <textarea name="veh_approval_remarks" id="remarks_approve" class="form-control" rows="3" placeholder="Enter approval remarks"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">check</i>
                                        Approve Application
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form action="{{ route('admin.security.vehicle_pass_approval.reject', encrypt($application->vehicle_tw_pk)) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this application?')">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="remarks_reject" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                        <textarea name="veh_approval_remarks" id="remarks_reject" class="form-control" rows="3" placeholder="Enter reason for rejection" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">close</i>
                                        Reject Application
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">info</i>
                            This application has already been processed and cannot be approved or rejected again.
                        </div>
                    </div>
                </div>
            @endif

            <!-- Close Button -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">close</i>
                        Close
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
