@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Application - Approval Details')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Vehicle Pass Application - Approval Details'])
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-body-tertiary border-bottom-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">directions_car</i>
                        </span>
                        <span>Vehicle Pass Application - Approval Review</span>
                        @if(isset($application->request_type) && $application->request_type === 'duplicate')
                            <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Duplicate Pass</span>
                        @else
                            <span class="badge bg-info-subtle text-info-emphasis ms-1">Regular Pass</span>
                        @endif
                    </h4>
                    <p class="text-body-secondary small mb-0">
                        Request ID:
                        <code class="fw-semibold">{{ $application->vehicle_req_id ?? $application->vehicle_tw_pk }}</code>
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_back</i>
                        <span>Back to Pending</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Application Status -->
            <div class="row mb-4">
                <div class="col-12">
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
                    <div class="alert alert-{{ $statusClass === 'secondary' ? 'secondary' : 'primary' }} bg-body-tertiary border-0 rounded-3 py-3 px-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <span class="small text-body-secondary text-uppercase fw-semibold">Status</span>
                                    <span class="mt-1">
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $statusText }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <span class="small text-body-secondary text-uppercase fw-semibold">Submitted Date</span>
                                    <span class="mt-1">
                                        {{ $application->created_date ? $application->created_date->format('d-M-Y H:i') : '--' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column">
                                    <span class="small text-body-secondary text-uppercase fw-semibold">Last Updated</span>
                                    <span class="mt-1">
                                        {{ $application->modified_date ? $application->modified_date->format('d-M-Y H:i') : 'Not updated' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Details -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3 d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">two_wheeler</i>
                        <span class="fw-semibold">Vehicle Details</span>
                    </h5>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Vehicle Type</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
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
                        <label class="form-label small fw-semibold text-body-secondary">Vehicle Number</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            <strong>{{ $application->vehicle_no }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Valid From</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            {{ $application->veh_card_valid_from ? \Carbon\Carbon::parse($application->veh_card_valid_from)->format('d-M-Y') : '--' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Valid To</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            {{ $application->vech_card_valid_to ? \Carbon\Carbon::parse($application->vech_card_valid_to)->format('d-M-Y') : '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Government Vehicle</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            {!! $application->gov_veh == 1 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Employee ID Card</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            {{ $application->employee_id_card ?? '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee Details -->
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3 d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">person</i>
                        <span class="fw-semibold">Employee Details</span>
                    </h5>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Employee ID</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            <strong>{{ $application->employee_id_card ?? '--' }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Employee Name</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            @if($application->employee)
                                {{ trim($application->employee->first_name . ' ' . ($application->employee->last_name ?? '')) ?: '--' }}
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Designation</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            @if($application->employee && $application->employee->designation)
                                {{ $application->employee->designation->designation_name ?? '--' }}
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Department</label>
                        <div class="form-control form-control-sm bg-body-tertiary border-0">
                            @if($application->employee && $application->employee->department)
                                {{ $application->employee->department->department_name ?? '--' }}
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
                        <h5 class="text-primary mb-3 d-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">attach_file</i>
                            <span class="fw-semibold">Attached Document</span>
                        </h5>
                        <a href="{{ Storage::url($application->doc_upload) }}" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">download</i>
                            <span>Download Document</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Approval History -->
            @if($application->approvals && count($application->approvals) > 0)
                <div class="row mb-3 mt-4">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3 d-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">history</i>
                            <span class="fw-semibold">Approval History</span>
                        </h5>
                        <div class="table-responsive rounded-3 border bg-body-tertiary">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Status</th>
                                        <th scope="col">Recommended</th>
                                        <th scope="col">Approved By</th>
                                        <th scope="col">Remarks</th>
                                        <th scope="col">Date</th>
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
                                                <small class="text-body-secondary">{{ $approval->veh_approval_remarks ?? '--' }}</small>
                                            </td>
                                            <td>
                                                <small class="text-body-secondary">{{ $approval->modified_date ? $approval->modified_date->format('d-M-Y H:i') : '--' }}</small>
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
                        <h5 class="text-primary mb-3 d-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">check_circle</i>
                            <span class="fw-semibold">Approval Actions</span>
                        </h5>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <form action="{{ route('admin.security.vehicle_pass_approval.approve', $application->encrypted_id) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="forward_status_approve" class="form-label small fw-semibold text-body-secondary">Forward Status</label>
                                        <select name="forward_status" id="forward_status_approve" class="form-select form-select-sm">
                                            <option value="1">Forwarded</option>
                                            <option value="2">Card Ready</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="remarks_approve" class="form-label small fw-semibold text-body-secondary">Remarks (Optional)</label>
                                        <textarea name="veh_approval_remarks" id="remarks_approve" class="form-control form-control-sm" rows="3" placeholder="Enter approval remarks"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">check</i>
                                        <span>Approve Application</span>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form action="{{ route('admin.security.vehicle_pass_approval.reject', $application->encrypted_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this application?')">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="remarks_reject" class="form-label small fw-semibold text-body-secondary">Rejection Reason <span class="text-danger">*</span></label>
                                        <textarea name="veh_approval_remarks" id="remarks_reject" class="form-control form-control-sm" rows="3" placeholder="Enter reason for rejection" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm d-inline-flex align-items-center gap-2">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">close</i>
                                        <span>Reject Application</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="alert alert-info d-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">info</i>
                            <span>This application has already been processed and cannot be approved or rejected again.</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Close Button -->
            <div class="row mt-3">
                <div class="col-md-12 d-flex justify-content-end">
                    <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">close</i>
                        <span>Close</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
