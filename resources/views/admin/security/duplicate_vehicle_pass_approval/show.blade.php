@extends('admin.layouts.master')
@section('title', 'Duplicate Vehicle Pass - Approval Review')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Duplicate Vehicle Pass - Approval Review</h4>
                    <small class="text-muted">Request ID: <code>{{ $application->vehicle_tw_pk }}</code></small>
                </div>
                <div>
                    <a href="{{ route('admin.security.duplicate_vehicle_pass_approval.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                        Back to Pending
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert" style="background-color: #f0f7ff; border-left: 4px solid #0066cc;">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Status:</strong><br>
                                @php
                                    $statusClass = match((int)$application->vech_card_status) {
                                        1 => 'warning',
                                        2 => 'success',
                                        3 => 'danger',
                                        default => 'secondary',
                                    };
                                    $statusText = $application->status_text;
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Submitted Date:</strong><br>
                                {{ $application->created_date ? $application->created_date->format('d-M-Y H:i') : '--' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Vehicle Category:</strong><br>
                                {{ $application->vehicle_category_display ?? '--' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">Vehicle Details</h5>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Vehicle Number</label>
                    <div class="form-control bg-light"><strong>{{ $application->vehicle_no }}</strong></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Original Vehicle Pass No</label>
                    <div class="form-control bg-light">{{ $application->vehicle_pass_no ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Vehicle Type</label>
                    <div class="form-control bg-light">{{ $application->vehicleType->vehicle_type ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Reason For Duplicate</label>
                    <div class="form-control bg-light">{{ $application->reason_for_duplicate_display ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Valid From</label>
                    <div class="form-control bg-light">
                        {{ $application->veh_card_valid_from ? $application->veh_card_valid_from->format('d-M-Y') : '--' }}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Valid To</label>
                    <div class="form-control bg-light">
                        {{ $application->vech_card_valid_to ? $application->vech_card_valid_to->format('d-M-Y') : '--' }}
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">Employee Details</h5>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Employee Name</label>
                    <div class="form-control bg-light">{{ $application->employee_name ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">ID Card Number</label>
                    <div class="form-control bg-light">{{ $application->employee_id_card ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Designation</label>
                    <div class="form-control bg-light">{{ $application->designation ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Department</label>
                    <div class="form-control bg-light">{{ $application->department ?? '--' }}</div>
                </div>
            </div>

            @if($application->doc_upload)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">Attached Document</h5>
                        <a href="{{ asset('storage/' . $application->doc_upload) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">download</i>
                            Download Document
                        </a>
                    </div>
                </div>
            @endif

            @if($application->approvals && $application->approvals->count() > 0)
                <div class="row mb-3 mt-4">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">Approval History</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr><th>Status</th><th>Approved By</th><th>Remarks</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($application->approvals as $approval)
                                        <tr>
                                            <td>
                                                <span class="badge {{ $approval->status == 2 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $approval->status == 2 ? 'Approved' : 'Rejected' }}
                                                </span>
                                            </td>
                                            <td>{{ $approval->approvedBy->first_name ?? '' }} {{ $approval->approvedBy->last_name ?? '--' }}</td>
                                            <td><small>{{ $approval->veh_approval_remarks ?? '--' }}</small></td>
                                            <td><small>{{ $approval->created_date ? $approval->created_date->format('d-M-Y H:i') : '--' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if($application->vech_card_status == \App\Models\VehiclePassDuplicateApplyTwfw::STATUS_PENDING)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">Approval Actions</h5>
                        <div class="d-flex gap-3 flex-wrap">
                            <form action="{{ route('admin.security.duplicate_vehicle_pass_approval.approve', encrypt($application->vehicle_tw_pk)) }}" method="POST" class="d-inline">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Remarks (Optional)</label>
                                    <textarea name="veh_approval_remarks" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this application?')">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">check</i>
                                    Approve
                                </button>
                            </form>
                            <form action="{{ route('admin.security.duplicate_vehicle_pass_approval.reject', encrypt($application->vehicle_tw_pk)) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this application?')">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="veh_approval_remarks" class="form-control" rows="2" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">close</i>
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info mt-4">This application has already been processed.</div>
            @endif

            <div class="mt-4">
                <a href="{{ route('admin.security.duplicate_vehicle_pass_approval.index') }}" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>
@endsection
