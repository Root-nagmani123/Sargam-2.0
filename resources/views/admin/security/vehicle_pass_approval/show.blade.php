@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Application - Approval Details')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* =====================================================================
   Vehicle Pass approval review — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   ===================================================================== */
.vehshow-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--ds-ink, #1f2937);
    margin: 0 0 var(--ds-space-3, 1rem);
    padding-bottom: var(--ds-space-2, 0.5rem);
    border-bottom: 1px solid var(--ds-line, #dee2e6);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
/* Read-only values render as label + value, not as fake disabled inputs. */
.vehshow-field {
    padding: 0.7rem 0;
    border-bottom: 1px solid var(--ds-line, #eef2f6);
}
.vehshow-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--ds-ink-muted, #667085);
    margin-bottom: 0.2rem;
}
.vehshow-value {
    display: block;
    font-size: 0.9375rem;
    color: var(--ds-ink, #1f2937);
    line-height: 1.4;
}

/* Summary strip: status / submitted / updated */
.vehshow-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--ds-space-3, 1rem); }
@media (max-width: 767.98px) { .vehshow-stats { grid-template-columns: 1fr; } }
.vehshow-stat {
    background: #fff;
    border: 1px solid var(--ds-line, #dee2e6);
    border-radius: var(--ds-radius-2, 8px);
    padding: 0.85rem 1.1rem;
}
.vehshow-stat-label { font-size: 0.75rem; font-weight: 500; color: var(--ds-ink-muted, #667085); margin-bottom: 0.35rem; }
.vehshow-stat-value { font-size: 0.9375rem; font-weight: 600; color: var(--ds-ink, #1f2937); line-height: 1.3; }

.vehshow-actions {
    margin-top: var(--ds-space-4, 1.5rem);
    padding-top: var(--ds-space-3, 1rem);
    border-top: 1px solid var(--ds-line, #dee2e6);
}
</style>
@endpush

@section('content')
@php
    $vehStatus = match ((int) ($application->vech_card_status ?? 0)) {
        1 => ['warning', 'Pending Approval'],
        2 => ['success', 'Approved'],
        3 => ['danger', 'Rejected'],
        default => ['secondary', 'Unknown'],
    };
    $isDuplicate = isset($application->request_type) && $application->request_type === 'duplicate';
    $docPath = $application->doc_upload;
    $docExists = $docPath && \Storage::disk('public')->exists($docPath);
@endphp

<div class="container-fluid vehicle-approval-show-page py-3">
    <x-breadcrum title="Vehicle Pass Application — Approval Review">
    </x-breadcrum>
    <x-session_message />

    <div class="ds-card">
        <div class="ds-card-body">

            {{-- Header: request identity --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-1 bg-{{ $isDuplicate ? 'warning text-dark' : 'info' }}">
                        {{ $isDuplicate ? 'Duplicate Pass' : 'Fresh Pass' }}
                    </span>
                    <span class="text-muted small">Request ID:
                        <code>{{ $application->vehicle_req_id ?? $application->vehicle_tw_pk }}</code>
                    </span>
                </div>
            </div>

            {{-- Summary strip --}}
            <div class="vehshow-stats mb-4">
                <div class="vehshow-stat">
                    <div class="vehshow-stat-label">Status</div>
                    <div class="vehshow-stat-value">
                        <span class="badge rounded-1 bg-{{ $vehStatus[0] }}">{{ $vehStatus[1] }}</span>
                    </div>
                </div>
                <div class="vehshow-stat">
                    <div class="vehshow-stat-label">Submitted Date</div>
                    <div class="vehshow-stat-value">{{ $application->created_date ? $application->created_date->format('d-M-Y H:i') : '--' }}</div>
                </div>
                <div class="vehshow-stat">
                    <div class="vehshow-stat-label">Last Updated</div>
                    <div class="vehshow-stat-value">{{ $application->modified_date ? $application->modified_date->format('d-M-Y H:i') : 'Not updated' }}</div>
                </div>
            </div>

            {{-- ============ Vehicle Details ============ --}}
            <h6 class="vehshow-section-title">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">two_wheeler</i>
                Vehicle Details
            </h6>
            <div class="row g-0 mb-4">
                <div class="col-md-6 pe-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Vehicle Type</span>
                        <span class="vehshow-value">{{ $application->vehicleType->vehicle_type ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Vehicle Number</span>
                        <span class="vehshow-value fw-semibold">{{ $application->vehicle_no ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 pe-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Valid From</span>
                        <span class="vehshow-value">{{ $application->veh_card_valid_from ? \Carbon\Carbon::parse($application->veh_card_valid_from)->format('d-M-Y') : '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Valid To</span>
                        <span class="vehshow-value">{{ $application->vech_card_valid_to ? \Carbon\Carbon::parse($application->vech_card_valid_to)->format('d-M-Y') : '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 pe-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Government Vehicle</span>
                        <span class="vehshow-value">
                            <span class="badge rounded-1 bg-{{ $application->gov_veh == 1 ? 'success' : 'secondary' }}">
                                {{ $application->gov_veh == 1 ? 'Yes' : 'No' }}
                            </span>
                        </span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Employee ID Card</span>
                        <span class="vehshow-value">{{ $application->employee_id_card ?? '--' }}</span>
                    </div>
                </div>
            </div>

            {{-- ============ Employee Details ============ --}}
            <h6 class="vehshow-section-title">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">person</i>
                Employee Details
            </h6>
            <div class="row g-0 mb-4">
                <div class="col-md-6 pe-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Employee ID</span>
                        <span class="vehshow-value fw-semibold">{{ $application->employee_id_card ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Employee Name</span>
                        <span class="vehshow-value">
                            @if($application->employee)
                                {{ trim($application->employee->first_name . ' ' . ($application->employee->last_name ?? '')) ?: '--' }}
                            @else
                                --
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6 pe-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Designation</span>
                        <span class="vehshow-value">{{ $application->employee->designation->designation_name ?? '--' }}</span>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="vehshow-field">
                        <span class="vehshow-label">Department</span>
                        <span class="vehshow-value">{{ $application->employee->department->department_name ?? '--' }}</span>
                    </div>
                </div>
            </div>

            {{-- ============ Attached Document ============ --}}
            @if($docPath)
                <h6 class="vehshow-section-title">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">attach_file</i>
                    Attached Document
                </h6>
                <div class="mb-4">
                    @if($docExists)
                        <a href="{{ asset('storage/' . $docPath) }}" target="_blank"
                           class="btn btn-outline-primary d-inline-flex align-items-center gap-2 rounded-1">
                            <i class="bi bi-download" aria-hidden="true"></i> Download Document
                        </a>
                    @else
                        <span class="text-warning small">No file available in storage</span>
                    @endif
                </div>
            @endif

            {{-- ============ Approval History ============ --}}
            @if($application->approvals && count($application->approvals) > 0)
                <h6 class="vehshow-section-title">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">history</i>
                    Approval History
                </h6>
                <div class="programme-dt-panel mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle programme-dt-table mb-0">
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
                                    @php
                                        $aStatus = match ((int) ($approval->status ?? 0)) {
                                            0 => ['warning', 'Pending'],
                                            1 => ['info', 'Forwarded'],
                                            2 => ['success', 'Approved'],
                                            3 => ['danger', 'Rejected'],
                                            default => ['secondary', '--'],
                                        };
                                        $aRecommend = match ((int) ($approval->veh_recommend_status ?? 0)) {
                                            1 => ['success', 'Recommended'],
                                            2 => ['success', 'Approved'],
                                            3 => ['danger', 'Not Recommended'],
                                            default => ['secondary', '--'],
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="badge rounded-1 bg-{{ $aStatus[0] }}">{{ $aStatus[1] }}</span></td>
                                        <td><span class="badge rounded-1 bg-{{ $aRecommend[0] }}">{{ $aRecommend[1] }}</span></td>
                                        <td>{{ $approval->approved_by_name ?? '--' }}</td>
                                        <td class="text-wrap" style="max-width:320px;">{{ $approval->veh_approval_remarks ?? '--' }}</td>
                                        <td>{{ $approval->modified_date ? $approval->modified_date->format('d-M-Y H:i') : '--' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ============ Approval Actions ============ --}}
            @if($canApprove ?? false)
                <h6 class="vehshow-section-title">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">check_circle</i>
                    Approval Actions
                </h6>
                <div class="row g-4">
                    <div class="col-md-6">
                        <form action="{{ route('admin.security.vehicle_pass_approval.approve', $application->encrypted_id) }}" method="POST">
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
                            <button type="submit" class="btn btn-success px-4 d-inline-flex align-items-center gap-2">
                                <i class="bi bi-check-circle" aria-hidden="true"></i> Approve Application
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('admin.security.vehicle_pass_approval.reject', $application->encrypted_id) }}" method="POST"
                              onsubmit="return confirm('Are you sure you want to reject this application?')">
                            @csrf
                            <div class="mb-3">
                                <label for="remarks_reject" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea name="veh_approval_remarks" id="remarks_reject" class="form-control" rows="3" placeholder="Enter reason for rejection" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger px-4 d-inline-flex align-items-center gap-2">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Reject Application
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-info d-flex align-items-center gap-2 mb-0" role="alert">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    This application has already been processed and cannot be approved or rejected again.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
