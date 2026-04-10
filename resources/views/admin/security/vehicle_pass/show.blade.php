@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Application - {{ $vehiclePass->vehicle_req_id ?? "N/A" }}')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Vehicle Pass Application Details</h4>
                    <small class="text-muted">Request ID: <code>{{ $vehiclePass->vehicle_req_id }}</code></small>
                </div>
                <div>
                    <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                        Back to List
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

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
                                    switch($vehiclePass->vech_card_status) {
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
                                <strong>Forward Status:</strong><br>
                                @php
                                    $forwardClass = '';
                                    $forwardText = '';
                                    switch($vehiclePass->veh_card_forward_status) {
                                        case 0:
                                            $forwardClass = 'secondary';
                                            $forwardText = 'Not Forwarded';
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
                            </div>
                            <div class="col-md-4">
                                <strong>Created Date:</strong><br>
                                {{ $vehiclePass->created_date ? $vehiclePass->created_date->format('d-M-Y H:i') : '--' }}
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
                            @if($vehiclePass->vehicleType)
                                {{ $vehiclePass->vehicleType->vehicle_type }}
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
                            <strong>{{ $vehiclePass->vehicle_no }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valid From</label>
                        <div class="form-control bg-light">
                            {{ $vehiclePass->veh_card_valid_from ? \Carbon\Carbon::parse($vehiclePass->veh_card_valid_from)->format('d-M-Y') : '--' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valid To</label>
                        <div class="form-control bg-light">
                            {{ $vehiclePass->vech_card_valid_to ? \Carbon\Carbon::parse($vehiclePass->vech_card_valid_to)->format('d-M-Y') : '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Government Vehicle</label>
                        <div class="form-control bg-light">
                            {!! $vehiclePass->gov_veh == 1 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Employee ID Card</label>
                        <div class="form-control bg-light">
                            {{ $vehiclePass->employee_id_card ?? '--' }}
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
            @php
                $resolvedEmpName = null;
                $resolvedEmpCode = null;
                if ($vehiclePass->employee) {
                    $resolvedEmpName = trim(($vehiclePass->employee->first_name ?? '') . ' ' . ($vehiclePass->employee->last_name ?? ''));
                    $resolvedEmpCode = $vehiclePass->employee->emp_id ?? $vehiclePass->employee_id_card;
                } else {
                    $resolvedEmpName = trim((string) ($vehiclePass->applicant_name ?? ''));
                    if ($resolvedEmpName === '') {
                        $resolvedEmpName = \App\Models\VehiclePassTWApply::resolveNameByEmployeeIdCard($vehiclePass->employee_id_card);
                    }
                    $resolvedEmpCode = $vehiclePass->employee_id_card;
                }
            @endphp
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Employee Name</label>
                        <div class="form-control bg-light">
                            @if($resolvedEmpName !== null && $resolvedEmpName !== '')
                                {{ $resolvedEmpName }}
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
                            {{ $resolvedEmpCode ?: '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachment -->
            @php
                $docPath = $vehiclePass->doc_upload;
                $docExists = $docPath && \Storage::disk('public')->exists($docPath);
            @endphp
            @if($docPath)
                <div class="row mb-3 mt-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">attach_file</i>
                            Attached Document
                        </h5>
                        @if($docExists)
                            <a href="{{ asset('storage/' . $docPath) }}" target="_blank" class="btn btn-outline-primary">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">download</i>
                                Download Document
                            </a>
                        @else
                            <span class="text-warning small">No file available in storage</span>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Approval History -->
            <div class="row mb-3 mt-4">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">history</i>
                        Approval History
                    </h5>
                </div>
            </div>
            @php
                $history = ($vehiclePass->approvals ?? collect())->sortBy('pk')->values();
            @endphp
            @if($history->isEmpty())
                <div class="alert alert-light border">
                    <span class="text-muted">No approval actions recorded yet.</span>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;" class="text-center">S.No.</th>
                                <th style="width:140px;" class="text-center">Status</th>
                                <th>Approved/Rejected By</th>
                                <th style="width:180px;">Action Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $i => $h)
                                @php
                                    $statusText = $h->status_text ?? (method_exists($h, 'getStatusTextAttribute') ? $h->status_text : null);
                                    $statusText = $statusText ?: match ((int) ($h->status ?? -1)) {
                                        0 => 'Pending',
                                        2 => 'Approved',
                                        3 => 'Rejected',
                                        default => 'Unknown',
                                    };
                                    $badge = match ($statusText) {
                                        'Approved' => 'success',
                                        'Rejected' => 'danger',
                                        'Pending' => 'warning',
                                        default => 'secondary',
                                    };
                                    $by = $h->approved_by_name ?? null;
                                    if (!$by && isset($h->approvedBy) && $h->approvedBy) {
                                        $by = $h->approvedBy->name ?? trim(($h->approvedBy->first_name ?? '') . ' ' . ($h->approvedBy->last_name ?? ''));
                                    }
                                    $actionAt = $h->modified_date ?? $h->created_date ?? null;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $badge }}">{{ $statusText }}</span>
                                    </td>
                                    <td>{{ $by ?: '--' }}</td>
                                    <td>{{ $actionAt ? \Carbon\Carbon::parse($actionAt)->format('d-M-Y H:i') : '--' }}</td>
                                    <td>{{ $h->veh_approval_remarks ?? '--' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-12">
                    @if($vehiclePass->vech_card_status == 1)
                        <a href="{{ route('admin.security.vehicle_pass.edit', encrypt($vehiclePass->vehicle_tw_pk)) }}" class="btn btn-warning">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">edit</i>
                            Edit Application
                        </a>
                        <form action="{{ route('admin.security.vehicle_pass.delete', encrypt($vehiclePass->vehicle_tw_pk)) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this application?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">delete</i>
                                Delete Application
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">close</i>
                        Close
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
