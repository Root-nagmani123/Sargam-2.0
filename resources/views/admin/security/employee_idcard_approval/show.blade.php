@extends('admin.layouts.master')
@section('title', 'ID Card Approval Details')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* =====================================================================
   ID Card approval review — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   ===================================================================== */
.idcshow-section-title {
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
/* Read-only values render as label + value, not as a bordered th/td grid. */
.idcshow-field { padding: 0.7rem 0; border-bottom: 1px solid var(--ds-line, #eef2f6); }
.idcshow-label {
    display: block; font-size: 0.75rem; font-weight: 500;
    color: var(--ds-ink-muted, #667085); margin-bottom: 0.2rem;
}
.idcshow-value { display: block; font-size: 0.9375rem; color: var(--ds-ink, #1f2937); line-height: 1.4; }

/* Summary strip */
.idcshow-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--ds-space-3, 1rem); }
@media (max-width: 767.98px) { .idcshow-stats { grid-template-columns: 1fr; } }
.idcshow-stat {
    background: #fff; border: 1px solid var(--ds-line, #dee2e6);
    border-radius: var(--ds-radius-2, 8px); padding: 0.85rem 1.1rem;
}
.idcshow-stat-label { font-size: 0.75rem; font-weight: 500; color: var(--ds-ink-muted, #667085); margin-bottom: 0.35rem; }
.idcshow-stat-value { font-size: 0.9375rem; font-weight: 600; color: var(--ds-ink, #1f2937); line-height: 1.3; }

/* Photo panel */
.idcshow-photo {
    max-height: 250px; width: auto; object-fit: cover;
    border: 1px solid var(--ds-line, #dee2e6); border-radius: var(--ds-radius-2, 8px);
}
.idcshow-doc-list { list-style: none; padding: 0; margin: 0; }
.idcshow-doc-list li {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.55rem 0; border-bottom: 1px solid var(--ds-line, #eef2f6); font-size: 0.875rem;
}
.idcshow-doc-list li:last-child { border-bottom: 0; }
</style>
@endpush

@section('content')
@php
    $stage = isset($stage) ? (int) $stage : (int) request()->get('stage');
    if (!in_array($stage, [1, 2, 3], true)) {
        $stage = $request->approved_by_a1 === null ? 1 : 2;
    }

    $stageLabel = $stage === 3 ? 'Approval III' : ($stage === 2 ? 'Approval II' : 'Approval I');
    $stageLevel = $stage === 3 ? 'Level 3' : ($stage === 2 ? 'Level 2' : 'Level 1');
    $backRoute = $stage === 3
        ? route('admin.security.employee_idcard_approval.approval3')
        : ($stage === 2
            ? route('admin.security.employee_idcard_approval.approval2')
            : route('admin.security.employee_idcard_approval.approval1'));
    $approveRoute = $stage === 3
        ? route('admin.security.employee_idcard_approval.approve3', encrypt($request->id))
        : ($stage === 2
            ? route('admin.security.employee_idcard_approval.approve2', encrypt($request->id))
            : route('admin.security.employee_idcard_approval.approve1', encrypt($request->id)));
    $rejectRoute = $stage === 3
        ? route('admin.security.employee_idcard_approval.reject3', encrypt($request->id))
        : ($stage === 2
            ? route('admin.security.employee_idcard_approval.reject2', encrypt($request->id))
            : route('admin.security.employee_idcard_approval.reject1', encrypt($request->id)));

    $showActions = $request->status === 'Pending' && ($canApprove ?? true);

    // At Approval II, a request already actioned at Level 2 stays "Pending" in the
    // master until final approval — show a clearer label for that case.
    $statusText = $request->status;
    if (($statusText ?? '') === 'Pending' && $stage === 2 && !($canApprove ?? true)) {
        $statusText = 'Pending from Final Approval';
    }
    $statusClass = match($request->status) {
        'Pending' => 'warning',
        'Approved' => 'success',
        'Rejected' => 'danger',
        'Issued' => 'primary',
        default => 'secondary'
    };

    $reqTypeEmpShort = match ($request->employee_type ?? null) {
        'Permanent Employee' => 'Permanent',
        'Contractual Employee' => 'Contractual',
        default => null,
    };
    $isDuplicateReq = isset($request->request_type) && $request->request_type === 'duplicate';

    $photoPath = null;
    $photoExists = false;
    if ($request->photo) {
        $photoPath = str_starts_with($request->photo, 'idcard/')
            ? $request->photo
            : 'idcard/photos/' . $request->photo;
        $photoExists = \Storage::disk('public')->exists($photoPath);
    }

    $fmtDate = function ($value) {
        if (empty($value)) {
            return '--';
        }
        try {
            $str = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
            return \Carbon\Carbon::parse($str)->format('d/m/Y');
        } catch (\Exception $e) {
            return (string) $value;
        }
    };

    $docLinks = [];
    if (!empty($request->joining_letter)) {
        // For permanent ID cards, joining_letter is already a full storage path.
        $docLinks[] = ['label' => 'Joining Letter', 'path' => ltrim($request->joining_letter, '/')];
    }
    if (!empty($request->extension_document_path)) {
        $docLinks[] = ['label' => 'Extension Document', 'path' => ltrim($request->extension_document_path, '/')];
    }
    if (!empty($request->fir_receipt)) {
        $docLinks[] = ['label' => 'FIR Copy / Document Proof', 'path' => 'idcard/dup_docs/' . ltrim($request->fir_receipt, '/')];
    }
    if (!empty($request->payment_receipt)) {
        $docLinks[] = ['label' => 'Payment Receipt', 'path' => 'idcard/dup_docs/' . ltrim($request->payment_receipt, '/')];
    }
    if (!empty($request->service_ext)) {
        $docLinks[] = ['label' => 'Service Extension / Renewal Proof', 'path' => 'idcard/dup_docs/' . ltrim($request->service_ext, '/')];
    }
    if (!empty($request->id_proof_doc)) {
        $docLinks[] = ['label' => 'ID Proof (Aadhar / Other)', 'path' => 'idcard/dup_docs/' . ltrim($request->id_proof_doc, '/')];
    }
    if (!empty($request->documents)) {
        $docLinks[] = ['label' => 'Supporting Document', 'path' => ltrim($request->documents, '/')];
    }
    if (!empty($request->other_documents)) {
        $docLinks[] = ['label' => 'Other Document', 'path' => 'idcard/dup_docs/' . ltrim($request->other_documents, '/')];
    }
    // De-dupe by storage path (contractual doc_path is exposed as both joining_letter and documents).
    $seenPaths = [];
    $docLinks = array_values(array_filter($docLinks, function ($d) use (&$seenPaths) {
        $p = $d['path'] ?? '';
        if ($p === '' || isset($seenPaths[$p])) {
            return false;
        }
        $seenPaths[$p] = true;
        return true;
    }));

    $details = [
        ['Employee Name', $request->name ?: '--'],
        ['Father Name', $request->father_name ?? '--'],
        ['Designation', $request->designation ?? '--'],
        ['Employee Type', $request->employee_type ?? ($request->card_type ?? '--')],
        ['Card Type (ID Type)', $request->card_type ?? '--'],
        ['ID Card No', $request->id_card_number ?? '--'],
        ['Request For', $request->request_for ?? '--'],
        ['Date of Birth', $fmtDate($request->date_of_birth ?? null)],
        ['Academy Joining Date', $fmtDate($request->academy_joining ?? null)],
        ['Blood Group', $request->blood_group ?? '--'],
        ['Contact No', $request->mobile_number ?? $request->telephone_number ?? '--'],
        ['Valid From', $request->id_card_valid_from ?? '--'],
        ['Valid Upto', $request->id_card_valid_upto ?? '--'],
        ['Request Date', $request->created_at ? $request->created_at->format('d/m/Y') : '--'],
        ['Requested By', $request->requested_by ?? '--'],
        ['Requested Section', $request->requested_section ?? '--'],
    ];
    $duplicateReason = $request->card_reason ?? ($request->duplication_reason ?? null);
@endphp

<div class="container-fluid idcard-approval-show-page py-3">
    <x-breadcrum title="ID Card Approval Details">
    </x-breadcrum>
    <x-session_message />

    <div class="ds-card">
        <div class="ds-card-body">

            {{-- Header: request identity + actions --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-1 bg-{{ $isDuplicateReq ? 'info' : 'secondary' }}">
                        {{ $isDuplicateReq ? 'Duplicate' : 'Fresh' }}{{ $reqTypeEmpShort ? ' (' . $reqTypeEmpShort . ')' : '' }}
                    </span>
                    <span class="text-muted small">Request ID: <code>{{ $request->id }}</code></span>
                </div>

                @if($showActions)
                    <div class="d-flex flex-wrap gap-2">
                        <form action="{{ $approveRoute }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success px-4 d-inline-flex align-items-center gap-2">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                Approve ({{ $stageLevel }})
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger px-4 d-inline-flex align-items-center gap-2"
                                data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                            Reject
                        </button>
                    </div>
                @endif
            </div>

            {{-- Summary strip --}}
            <div class="idcshow-stats mb-4">
                <div class="idcshow-stat">
                    <div class="idcshow-stat-label">Status</div>
                    <div class="idcshow-stat-value">
                        <span class="badge rounded-1 bg-{{ $statusClass }}"
                              @if(($request->status ?? '') === 'Approved') title="Please collect your ID card from security section" @endif>{{ $statusText }}</span>
                    </div>
                </div>
                <div class="idcshow-stat">
                    <div class="idcshow-stat-label">Approval Level</div>
                    <div class="idcshow-stat-value">{{ $stageLabel }}</div>
                </div>
                <div class="idcshow-stat">
                    <div class="idcshow-stat-label">Request Date</div>
                    <div class="idcshow-stat-value">{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</div>
                </div>
            </div>

            <div class="row g-4">
                {{-- ============ Photo + Documents ============ --}}
                <div class="col-lg-5">
                    <h6 class="idcshow-section-title">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">badge</i>
                        Employee Photo
                    </h6>
                    <div class="text-center mb-4">
                        @if($photoExists)
                            <img src="{{ asset('storage/' . $photoPath) }}" alt="Employee Photo" class="idcshow-photo">
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" rel="noopener"
                                   class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 rounded-1">
                                    <i class="bi bi-eye" aria-hidden="true"></i> View
                                </a>
                            </div>
                        @else
                            <img src="{{ asset('images/dummypic.jpeg') }}" alt="No Photo" class="idcshow-photo">
                            <p class="{{ $request->photo ? 'text-warning' : 'text-muted' }} small mt-2 mb-0">
                                {{ $request->photo ? 'No file available in storage' : 'No photo available' }}
                            </p>
                        @endif
                    </div>

                    @if(!empty($docLinks))
                        <h6 class="idcshow-section-title">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">attach_file</i>
                            Uploaded Documents
                        </h6>
                        <ul class="idcshow-doc-list">
                            @foreach($docLinks as $doc)
                                @php
                                    $p = $doc['path'] ?? null;
                                    $exists = $p && \Storage::disk('public')->exists($p);
                                @endphp
                                <li>
                                    <i class="bi bi-paperclip text-muted" aria-hidden="true"></i>
                                    @if($exists)
                                        <a href="{{ asset('storage/' . $p) }}" target="_blank" rel="noopener">{{ $doc['label'] }}</a>
                                    @else
                                        <span class="text-warning">{{ $doc['label'] }} — No file available in storage</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- ============ Request Details ============ --}}
                <div class="col-lg-7">
                    <h6 class="idcshow-section-title">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">description</i>
                        Request Details
                    </h6>
                    <div class="row g-0">
                        @foreach($details as [$label, $value])
                            <div class="col-md-6 {{ $loop->even ? 'ps-md-4' : 'pe-md-4' }}">
                                <div class="idcshow-field">
                                    <span class="idcshow-label">{{ $label }}</span>
                                    <span class="idcshow-value">{{ $value !== '' && $value !== null ? $value : '--' }}</span>
                                </div>
                            </div>
                        @endforeach

                        <div class="col-md-6 {{ count($details) % 2 === 0 ? 'pe-md-4' : 'ps-md-4' }}">
                            <div class="idcshow-field">
                                <span class="idcshow-label">Request Type</span>
                                <span class="idcshow-value">
                                    <span class="badge rounded-1 bg-{{ $isDuplicateReq ? 'info' : 'secondary' }}">
                                        {{ $isDuplicateReq ? 'Duplicate' : 'Fresh' }}{{ $reqTypeEmpShort ? ' (' . $reqTypeEmpShort . ')' : '' }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        @if(!empty($duplicateReason))
                            <div class="col-md-6 {{ count($details) % 2 === 0 ? 'ps-md-4' : 'pe-md-4' }}">
                                <div class="idcshow-field">
                                    <span class="idcshow-label">Duplicate Reason</span>
                                    <span class="idcshow-value">{{ $duplicateReason }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($showActions)
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ $rejectRoute }}" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Enter Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
