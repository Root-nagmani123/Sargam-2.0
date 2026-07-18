@extends('admin.layouts.master')

@section('title', 'Vehicle Pass Application - ' . ($vehiclePass->vehicle_req_id ?? 'N/A'))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid vehicle-pass-show-page">
    <x-breadcrum title="Vehicle Pass Application">
    </x-breadcrum>

    <x-session_message />

    @php
        $statusMap = [
            1 => ['label' => 'Pending Approval', 'badge' => 'bg-warning text-dark'],
            2 => ['label' => 'Approved',         'badge' => 'bg-success'],
            3 => ['label' => 'Rejected',         'badge' => 'bg-danger'],
        ];
        $status = $statusMap[(int) $vehiclePass->vech_card_status] ?? ['label' => 'Unknown', 'badge' => 'bg-secondary'];

        $forwardMap = [
            0 => ['label' => 'Not Forwarded', 'badge' => 'bg-secondary'],
            1 => ['label' => 'Forwarded',     'badge' => 'bg-info'],
            2 => ['label' => 'Card Ready',    'badge' => 'bg-success'],
        ];
        $forward = $forwardMap[(int) $vehiclePass->veh_card_forward_status] ?? ['label' => '--', 'badge' => 'bg-secondary'];

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

        $docPath = $vehiclePass->doc_upload;
        $docExists = $docPath && \Storage::disk('public')->exists($docPath);

        $history = ($vehiclePass->approvals ?? collect())->sortBy('pk')->values();
    @endphp

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3 p-lg-4">

            {{-- Header: request id + status badges --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h5 class="mb-1 fw-semibold">Request #{{ $vehiclePass->vehicle_req_id ?? 'N/A' }}</h5>
                    <p class="text-muted small mb-0">Detailed information for this vehicle pass application.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge px-3 py-2 {{ $status['badge'] }}">{{ $status['label'] }}</span>
                    <span class="badge px-3 py-2 {{ $forward['badge'] }}">{{ $forward['label'] }}</span>
                </div>
            </div>

            {{-- Vehicle Details --}}
            <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-bicycle" aria-hidden="true"></i> Vehicle Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Vehicle Type</div>
                    <div class="fw-semibold">{{ $vehiclePass->vehicleType->vehicle_type ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Vehicle Number</div>
                    <div class="fw-semibold">{{ $vehiclePass->vehicle_no ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Valid From</div>
                    <div class="fw-semibold">{{ $vehiclePass->veh_card_valid_from ? \Carbon\Carbon::parse($vehiclePass->veh_card_valid_from)->format('d-M-Y') : '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Valid To</div>
                    <div class="fw-semibold">{{ $vehiclePass->vech_card_valid_to ? \Carbon\Carbon::parse($vehiclePass->vech_card_valid_to)->format('d-M-Y') : '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Government Vehicle</div>
                    <div class="fw-semibold">
                        @if((int) $vehiclePass->gov_veh === 1)
                            <span class="badge rounded-1 bg-success">Yes</span>
                        @else
                            <span class="badge rounded-1 bg-secondary">No</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Created Date</div>
                    <div class="fw-semibold">{{ $vehiclePass->created_date ? $vehiclePass->created_date->format('d-M-Y H:i') : '--' }}</div>
                </div>
            </div>

            {{-- Employee Details --}}
            <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-person" aria-hidden="true"></i> Employee Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Employee Name</div>
                    <div class="fw-semibold">{{ ($resolvedEmpName !== null && $resolvedEmpName !== '') ? $resolvedEmpName : '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Employee Code</div>
                    <div class="fw-semibold">{{ $resolvedEmpCode ?: '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Employee ID Card</div>
                    <div class="fw-semibold">{{ $vehiclePass->employee_id_card ?? '--' }}</div>
                </div>
            </div>

            {{-- Attached Document --}}
            @if($docPath)
                <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-paperclip" aria-hidden="true"></i> Attached Document
                </h6>
                <div class="mb-4">
                    @if($docExists)
                        <a href="{{ asset('storage/' . $docPath) }}" target="_blank"
                           class="btn btn-outline-primary rounded-1 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            <span>Download Document</span>
                        </a>
                    @else
                        <span class="text-warning small">No file available in storage</span>
                    @endif
                </div>
            @endif

            {{-- Approval History --}}
            <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-clock-history" aria-hidden="true"></i> Approval History
            </h6>
            @if($history->isEmpty())
                <div class="alert alert-light border rounded-3 mb-4">
                    <span class="text-muted">No approval actions recorded yet.</span>
                </div>
            @else
                <div class="table-responsive border rounded-3 mb-4">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;" class="text-center">S.No.</th>
                                <th style="width:150px;" class="text-center">Status</th>
                                <th>Approved / Rejected By</th>
                                <th style="width:180px;">Action Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $i => $h)
                                @php
                                    $hStatusText = $h->status_text ?? null;
                                    $hStatusText = $hStatusText ?: match ((int) ($h->status ?? -1)) {
                                        0 => 'Pending',
                                        2 => 'Approved',
                                        3 => 'Rejected',
                                        default => 'Unknown',
                                    };
                                    $hBadge = match ($hStatusText) {
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        'Pending'  => 'bg-warning text-dark',
                                        default    => 'bg-secondary',
                                    };
                                    $by = $h->approved_by_name ?? null;
                                    if (!$by && isset($h->approvedBy) && $h->approvedBy) {
                                        $by = $h->approvedBy->name ?? trim(($h->approvedBy->first_name ?? '') . ' ' . ($h->approvedBy->last_name ?? ''));
                                    }
                                    $actionAt = $h->modified_date ?? $h->created_date ?? null;
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="text-center"><span class="badge rounded-1 {{ $hBadge }}">{{ $hStatusText }}</span></td>
                                    <td>{{ $by ?: '--' }}</td>
                                    <td>{{ $actionAt ? \Carbon\Carbon::parse($actionAt)->format('d-M-Y H:i') : '--' }}</td>
                                    <td>{{ $h->veh_approval_remarks ?? '--' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                @if((int) $vehiclePass->vech_card_status === 1 && ($canModifyApplication ?? false))
                    <a href="{{ route('admin.security.vehicle_pass.edit', encrypt($vehiclePass->vehicle_tw_pk)) }}"
                       class="btn btn-primary rounded-1 d-inline-flex align-items-center gap-2 mt-3">
                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit Application
                    </a>
                    <form action="{{ route('admin.security.vehicle_pass.delete', encrypt($vehiclePass->vehicle_tw_pk)) }}" method="POST"
                          class="d-inline mt-3" onsubmit="return confirm('Are you sure you want to delete this application?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger rounded-1 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-trash3" aria-hidden="true"></i> Delete Application
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.security.vehicle_pass.index') }}"
                   class="btn btn-outline-secondary rounded-1 d-inline-flex align-items-center gap-2 mt-3">
                    <i class="bi bi-x-lg" aria-hidden="true"></i> Close
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
