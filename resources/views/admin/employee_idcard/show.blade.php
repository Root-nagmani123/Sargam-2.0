@extends('admin.layouts.master')

@section('title', 'View ID Card Request - Sargam | Lal Bahadur Shastri')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid employee-idcard-show-page">
    <x-breadcrum title="Employee ID Card Request Details">
        <div class="d-flex flex-wrap gap-2">
            @if($request->user_may_edit_request ?? false)
            <a href="{{ route('admin.employee_idcard.edit', $request->id) }}"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
                <i class="bi bi-pencil" aria-hidden="true"></i> <span>Edit Request</span>
            </a>
            @endif
            @if($request->user_may_edit_request ?? false)
            <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this request?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="btn btn-outline-danger rounded-1 w-100 d-inline-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-trash3" aria-hidden="true"></i> Delete Request
                </button>
            </form>
            @endif
        </div>
    </x-breadcrum>

    <x-session_message />

    @php
    $statusClass = match($request->status) {
    'Pending' => 'bg-warning text-dark',
    'Approved' => 'bg-success',
    'Rejected' => 'bg-danger',
    'Issued' => 'bg-primary',
    default => 'bg-secondary'
    };
    @endphp

    {{-- Header --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="mb-1 fw-semibold">Request #{{ $request->id }}</h5>
                    <p class="text-muted small mb-0">
                        Created: {{ $request->created_at ? $request->created_at->format('d/m/Y H:i') : '--' }}
                    </p>
                </div>
                <div class="text-end">
                    <div class="text-muted small mb-1">Current Status</div>
                    <span class="badge rounded-1 px-3 py-2 {{ $statusClass }}" @if(($request->status ?? '') ===
                        'Approved') data-bs-toggle="tooltip" title="Please collect your ID card from security section"
                        @endif>
                        {{ $request->status ?? '--' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main content --}}
        <div class="col-lg-8">

            {{-- Employee Type --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-person-vcard" aria-hidden="true"></i> Employee Type
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small mb-1">Type</div>
                            <div class="fw-semibold">{{ $request->employee_type ?? '--' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small mb-1">Card Type</div>
                            <div class="fw-semibold">{{ $request->card_type ?? '--' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small mb-1">Sub Type</div>
                            <div class="fw-semibold">{{ $request->sub_type ?? '--' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Personal Information --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle" aria-hidden="true"></i> Personal Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Full Name</div>
                            <div class="fw-semibold">{{ $request->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Designation</div>
                            <div class="fw-semibold">{{ $request->designation ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Date of Birth</div>
                            <div class="fw-semibold">
                                @if($request->date_of_birth)
                                {{ \Carbon\Carbon::parse($request->date_of_birth)->format('d M, Y') }}
                                @else
                                --
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Father Name</div>
                            <div class="fw-semibold">{{ $request->father_name ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Academy Joining Date</div>
                            <div class="fw-semibold">
                                @if($request->academy_joining)
                                {{ \Carbon\Carbon::parse($request->academy_joining)->format('d M, Y') }}
                                @else
                                --
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Blood Group</div>
                            <div class="fw-semibold text-danger">{{ $request->blood_group ?? '--' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-telephone" aria-hidden="true"></i> Contact Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Mobile Number</div>
                            <div class="fw-semibold">{{ $request->mobile_number ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Telephone Number</div>
                            <div class="fw-semibold">{{ $request->telephone_number ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Section</div>
                            <div class="fw-semibold">{{ $request->section ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">ID Card Valid Upto</div>
                            <div class="fw-semibold">{{ $request->id_card_valid_upto ?? '--' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Additional Details --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-building" aria-hidden="true"></i> Additional Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Approval Authority</div>
                            <div class="fw-semibold">{{ $request->approval_authority_name ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Vendor / Organization</div>
                            <div class="fw-semibold">{{ $request->vendor_organization_name ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Request For</div>
                            <div class="fw-semibold">{{ $request->request_for ?? '--' }}</div>
                        </div>
                        @if(in_array($request->request_for, ['Replacement', 'Duplication']) &&
                        $request->duplication_reason)
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Duplication</div>
                            @php
                            $dupBadge = match($request->duplication_reason) {
                            'Lost' => 'bg-danger',
                            'Damage' => 'bg-warning text-dark',
                            'Expired Card' => 'bg-info',
                            default => 'bg-secondary'
                            };
                            @endphp
                            <span class="badge rounded-1 {{ $dupBadge }}">{{ $request->duplication_reason }}</span>
                        </div>
                        @endif
                        @if($request->id_card_number)
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">ID Card Number</div>
                            <div class="fw-semibold">{{ $request->id_card_number }}</div>
                        </div>
                        @endif
                        @if($request->id_card_valid_from)
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">ID Card Valid From</div>
                            <div class="fw-semibold">{{ $request->id_card_valid_from }}</div>
                        </div>
                        @endif
                        @if($request->request_for == 'Extension')
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Extension</div>
                            <div class="fw-semibold">{{ $request->id_card_valid_upto ?? '--' }}</div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">Request Date</div>
                            <div class="fw-semibold">
                                {{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Remarks --}}
            @if($request->remarks)
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-chat-left-text" aria-hidden="true"></i> Remarks
                    </h6>
                    <p class="mb-0">{{ $request->remarks }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            {{-- Attached Documents --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-paperclip" aria-hidden="true"></i> Attached Documents
                    </h6>
                    <div class="row g-2">
                        @php
                        $photoExists = $request->photo && \Storage::disk('public')->exists($request->photo);
                        @endphp
                        @if($photoExists)
                        <div class="col-12">
                            <div class="border rounded-3 p-3 text-center">
                                <i class="bi bi-image text-primary" style="font-size:40px;"></i>
                                <small class="d-block mt-2 text-muted">Photo</small>
                                <a href="{{ asset('storage/' . $request->photo) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary rounded-1 mt-2 w-100">
                                    <i class="bi bi-download me-1" aria-hidden="true"></i> View / Download
                                </a>
                            </div>
                        </div>
                        @else
                        <div class="col-12">
                            <div class="border rounded-3 p-3 text-center">
                                <i class="bi bi-image text-muted" style="font-size:40px; opacity:0.5;"></i>
                                <small class="d-block mt-2 text-muted">No photo uploaded</small>
                            </div>
                        </div>
                        @endif

                        @php
                        $supportingPath = $request->joining_letter ?? $request->documents;
                        $supportingExists = $supportingPath && \Storage::disk('public')->exists($supportingPath);
                        $supportingLabel = ($request->employee_type ?? '') === 'Contractual Employee'
                        ? 'Supporting document'
                        : 'Joining Letter';
                        @endphp
                        @if($supportingExists)
                        <div class="col-12">
                            <div class="border rounded-3 p-3 text-center">
                                <i class="bi bi-file-earmark-text text-info" style="font-size:40px;"></i>
                                <small class="d-block mt-2 text-muted">{{ $supportingLabel }}</small>
                                <a href="{{ asset('storage/' . $supportingPath) }}" target="_blank"
                                    class="btn btn-sm btn-outline-info rounded-1 mt-2 w-100">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i> View / Download
                                </a>
                            </div>
                        </div>
                        @endif

                        @php
                        $firExists = $request->fir_receipt && \Storage::disk('public')->exists($request->fir_receipt);
                        @endphp
                        @if($firExists)
                        <div class="col-12">
                            <div class="border rounded-3 p-3 text-center">
                                <i class="bi bi-file-earmark-medical text-warning" style="font-size:40px;"></i>
                                <small class="d-block mt-2 text-muted">FIR Receipt</small>
                                <a href="{{ asset('storage/' . $request->fir_receipt) }}" target="_blank"
                                    class="btn btn-sm btn-outline-warning rounded-1 mt-2 w-100">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i> View / Download
                                </a>
                            </div>
                        </div>
                        @endif

                        @php
                        $paymentExists = $request->payment_receipt &&
                        \Storage::disk('public')->exists($request->payment_receipt);
                        @endphp
                        @if($paymentExists)
                        <div class="col-12">
                            <div class="border rounded-3 p-3 text-center">
                                <i class="bi bi-receipt text-success" style="font-size:40px;"></i>
                                <small class="d-block mt-2 text-muted">Payment Receipt</small>
                                <a href="{{ asset('storage/' . $request->payment_receipt) }}" target="_blank"
                                    class="btn btn-sm btn-outline-success rounded-1 mt-2 w-100">
                                    <i class="bi bi-eye me-1" aria-hidden="true"></i> View / Download
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Quick Info --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle" aria-hidden="true"></i> Quick Info
                    </h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Created By:</span>
                        <strong>{{ $request->created_by_name ?? '--' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Last Updated:</span>
                        <strong>{{ $request->updated_at ? $request->updated_at->diffForHumans() : '--' }}</strong>
                    </div>
                    @if($request->approver1)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Approved By A1:</span>
                        <strong class="text-success">{{ $request->approver1->name ?? '--' }}</strong>
                    </div>
                    @endif
                    @if($request->approver2)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Approved By A2:</span>
                        <strong class="text-success">{{ $request->approver2->name ?? '--' }}</strong>
                    </div>
                    @endif
                    @if($request->rejection_reason)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Rejection Reason:</span>
                        <strong class="text-danger small">{{ Str::limit($request->rejection_reason, 30) }}</strong>
                    </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Status:</span>
                        <span class="badge rounded-1 {{ $statusClass }}" @if(($request->status ?? '') === 'Approved')
                            data-bs-toggle="tooltip" title="Please collect your ID card from security section"
                            @endif>{{ $request->status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            try {
                bootstrap.Tooltip.getOrCreateInstance(el);
            } catch (e) {}
        });
    }
});
</script>
@endpush
@endsection