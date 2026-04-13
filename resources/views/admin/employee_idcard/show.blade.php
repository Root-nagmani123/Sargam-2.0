@extends('admin.layouts.master')
@section('title', 'View ID Card Request - Sargam | Lal Bahadur Shastri')
@section('content')
<div class="container-fluid py-2">
    <x-breadcrum title="Employee ID Card Request Details"></x-breadcrum>
    <x-session_message />

    @php
        $statusClass = match($request->status) {
            'Pending' => 'warning',
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Issued' => 'primary',
            default => 'secondary'
        };
        $statusIcon = match($request->status) {
            'Pending' => 'schedule',
            'Approved' => 'check_circle',
            'Rejected' => 'cancel',
            'Issued' => 'card_giftcard',
            default => 'help'
        };
    @endphp

    <!-- Hero summary: accent rail avoids border-0 vs border-start conflict -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="d-flex">
            <div class="border-start border-5 border-primary flex-shrink-0 align-self-stretch rounded-start" aria-hidden="true"></div>
            <div class="flex-grow-1 min-w-0">
        <div class="card-body p-4 p-lg-4 border-0">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 d-none d-sm-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="material-icons material-symbols-rounded fs-3 lh-1">visibility</i>
                        </div>
                        <div>
                            <h1 class="h4 mb-2 fw-bold text-body-emphasis">Employee ID Card Request Details</h1>
                            <p class="text-body-secondary mb-0 small">
                                <span class="me-2">Request ID <span class="fw-semibold text-body">#{{ $request->id }}</span></span>
                                <span class="vr align-middle d-none d-md-inline text-body-tertiary"></span>
                                <span class="d-block d-md-inline mt-1 mt-md-0 ms-md-2">
                                    Created <span class="fw-semibold text-body">{{ $request->created_at ? $request->created_at->format('d/m/Y H:i') : '--' }}</span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="w-100 w-lg-auto text-lg-end border-top border-lg-0 border-light-subtle pt-3 pt-lg-0">
                    <p class="text-body-secondary text-uppercase small fw-semibold mb-2 mb-lg-1">Current status</p>
                    <span class="badge bg-{{ $statusClass }} rounded-pill px-3 py-2 fs-6 fw-semibold d-inline-flex align-items-center gap-1"
                          @if(($request->status ?? '') === 'Approved') title="Please collect your ID card from security section" @endif>
                        <i class="material-icons material-symbols-rounded fs-6 lh-1">{{ $statusIcon }}</i>
                        {{ $request->status }}
                    </span>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Employee Type -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                        <i class="material-icons material-symbols-rounded fs-5 lh-1">person_badge</i>
                    </span>
                    <h2 class="h6 mb-0 fw-bold text-body-emphasis">Employee Type</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 row-cols-1 row-cols-md-3">
                        <div class="col">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Type</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->employee_type }}</span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Card Type</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->card_type ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Sub Type</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->sub_type ?? '--' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                        <i class="material-icons material-symbols-rounded fs-5 lh-1">info</i>
                    </span>
                    <h2 class="h6 mb-0 fw-bold text-body-emphasis">Personal Information</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Full Name</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->name }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Designation</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->designation ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Date of Birth</small>
                                <span class="text-body-emphasis fw-semibold d-block">
                                    @if($request->date_of_birth)
                                        {{ \Carbon\Carbon::parse($request->date_of_birth)->format('d M, Y') }}
                                    @else
                                        --
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Father Name</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->father_name ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Academy Joining Date</small>
                                <span class="text-body-emphasis fw-semibold d-block">
                                    @if($request->academy_joining)
                                        {{ \Carbon\Carbon::parse($request->academy_joining)->format('d M, Y') }}
                                    @else
                                        --
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Blood Group</small>
                                <span class="text-danger fw-bold d-block">{{ $request->blood_group ?? '--' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                        <i class="material-icons material-symbols-rounded fs-5 lh-1">phone</i>
                    </span>
                    <h2 class="h6 mb-0 fw-bold text-body-emphasis">Contact Information</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Mobile Number</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->mobile_number ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Telephone Number</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->telephone_number ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Section</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->section ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">ID Card Valid Upto</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->id_card_valid_upto ?? '--' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                        <i class="material-icons material-symbols-rounded fs-5 lh-1">domain</i>
                    </span>
                    <h2 class="h6 mb-0 fw-bold text-body-emphasis">Additional Details</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Approval Authority</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->approval_authority_name ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Vendor / Organization</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->vendor_organization_name ?? '--' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Request For</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->request_for ?? '--' }}</span>
                            </div>
                        </div>
                        @if(in_array($request->request_for, ['Replacement', 'Duplication']) && $request->duplication_reason)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Duplication</small>
                                @php
                                    $dupBadge = match($request->duplication_reason) {
                                        'Lost' => 'danger',
                                        'Damage' => 'warning',
                                        'Expired Card' => 'info',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $dupBadge }} rounded-pill px-3 py-2 @if(in_array($dupBadge, ['warning', 'info'])) text-dark @endif">{{ $request->duplication_reason }}</span>
                            </div>
                        </div>
                        @endif
                        @if($request->id_card_number)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">ID Card Number</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->id_card_number }}</span>
                            </div>
                        </div>
                        @endif
                        @if($request->id_card_valid_from)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">ID Card Valid From</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->id_card_valid_from }}</span>
                            </div>
                        </div>
                        @endif
                        @if($request->request_for == 'Extension')
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Extension</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->id_card_valid_upto ?? '--' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-light-subtle bg-body-tertiary h-100">
                                <small class="text-body-secondary text-uppercase fw-semibold d-block mb-2">Request Date</small>
                                <span class="text-body-emphasis fw-semibold d-block">{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            @if($request->remarks)
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                        <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                            <i class="material-icons material-symbols-rounded fs-5 lh-1">comment</i>
                        </span>
                        <h2 class="h6 mb-0 fw-bold text-body-emphasis">Remarks</h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-light border border-light-subtle text-body-emphasis mb-0 rounded-3" role="note">
                            {{ $request->remarks }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sticky-lg-top" style="top: 1rem; z-index: 1;">
            <!-- Documents Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                        <i class="material-icons material-symbols-rounded fs-5 lh-1">attachment</i>
                    </span>
                    <h2 class="h6 mb-0 fw-bold text-body-emphasis">Attached Documents</h2>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div class="vstack gap-3">
                        @php
                            $photoExists = $request->photo && \Storage::disk('public')->exists($request->photo);
                        @endphp
                        @if($photoExists)
                            <div class="list-group list-group-flush rounded-3 border border-light-subtle overflow-hidden">
                                <div class="list-group-item bg-body-tertiary border-0 p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 bg-primary-subtle text-primary p-3 flex-shrink-0">
                                            <i class="material-icons material-symbols-rounded fs-2 lh-1">image</i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0 text-start">
                                            <div class="fw-semibold text-body-emphasis">Photo</div>
                                            <small class="text-body-secondary">Passport-style image</small>
                                        </div>
                                    </div>
                                    <a href="{{ asset('storage/' . $request->photo) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary w-100 mt-3 d-inline-flex align-items-center justify-content-center gap-2 rounded-3">
                                        <i class="material-icons material-symbols-rounded fs-6 lh-1">download</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="text-center p-4 rounded-3 border border-dashed border-secondary-subtle bg-body-tertiary">
                                <i class="material-icons material-symbols-rounded text-body-secondary opacity-50 fs-1 lh-1">image</i>
                                <p class="text-body-secondary small mb-0 mt-2">No photo uploaded</p>
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
                            <div class="list-group list-group-flush rounded-3 border border-light-subtle overflow-hidden">
                                <div class="list-group-item bg-body-tertiary border-0 p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 bg-info-subtle text-info p-3 flex-shrink-0">
                                            <i class="material-icons material-symbols-rounded fs-2 lh-1">description</i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0 text-start">
                                            <div class="fw-semibold text-body-emphasis">{{ $supportingLabel }}</div>
                                            <small class="text-body-secondary">Official document</small>
                                        </div>
                                    </div>
                                    <a href="{{ asset('storage/' . $supportingPath) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info w-100 mt-3 d-inline-flex align-items-center justify-content-center gap-2 rounded-3">
                                        <i class="material-icons material-symbols-rounded fs-6 lh-1">visibility</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @endif

                        @php
                            $firExists = $request->fir_receipt && \Storage::disk('public')->exists($request->fir_receipt);
                        @endphp
                        @if($firExists)
                            <div class="list-group list-group-flush rounded-3 border border-light-subtle overflow-hidden">
                                <div class="list-group-item bg-body-tertiary border-0 p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 bg-warning-subtle text-warning-emphasis p-3 flex-shrink-0">
                                            <i class="material-icons material-symbols-rounded fs-2 lh-1">gavel</i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0 text-start">
                                            <div class="fw-semibold text-body-emphasis">FIR Receipt</div>
                                            <small class="text-body-secondary">For lost / damaged card</small>
                                        </div>
                                    </div>
                                    <a href="{{ asset('storage/' . $request->fir_receipt) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-warning w-100 mt-3 d-inline-flex align-items-center justify-content-center gap-2 rounded-3">
                                        <i class="material-icons material-symbols-rounded fs-6 lh-1">visibility</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @endif

                        @php
                            $paymentExists = $request->payment_receipt && \Storage::disk('public')->exists($request->payment_receipt);
                        @endphp
                        @if($paymentExists)
                            <div class="list-group list-group-flush rounded-3 border border-light-subtle overflow-hidden">
                                <div class="list-group-item bg-body-tertiary border-0 p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 bg-success-subtle text-success p-3 flex-shrink-0">
                                            <i class="material-icons material-symbols-rounded fs-2 lh-1">receipt</i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0 text-start">
                                            <div class="fw-semibold text-body-emphasis">Payment Receipt</div>
                                            <small class="text-body-secondary">Payment proof</small>
                                        </div>
                                    </div>
                                    <a href="{{ asset('storage/' . $request->payment_receipt) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success w-100 mt-3 d-inline-flex align-items-center justify-content-center gap-2 rounded-3">
                                        <i class="material-icons material-symbols-rounded fs-6 lh-1">visibility</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            <!-- Quick Info Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                        <i class="material-icons material-symbols-rounded fs-5 lh-1">info</i>
                    </span>
                    <h2 class="h6 mb-0 fw-bold text-body-emphasis">Quick Info</h2>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush rounded-0">
                        <li class="list-group-item border-light-subtle px-4 py-3 d-flex justify-content-between align-items-start gap-2">
                            <span class="text-body-secondary small">Created by</span>
                            <span class="fw-semibold text-body-emphasis text-end">{{ $request->created_by_name ?? '--' }}</span>
                        </li>
                        <li class="list-group-item border-light-subtle px-4 py-3 d-flex justify-content-between align-items-start gap-2">
                            <span class="text-body-secondary small">Last updated</span>
                            <span class="fw-semibold text-body-emphasis text-end">{{ $request->updated_at->diffForHumans() }}</span>
                        </li>
                        @if($request->approver1)
                        <li class="list-group-item border-light-subtle px-4 py-3 d-flex justify-content-between align-items-start gap-2">
                            <span class="text-body-secondary small">Approved by A1</span>
                            <span class="fw-semibold text-success text-end">{{ $request->approver1->name ?? '--' }}</span>
                        </li>
                        @endif
                        @if($request->approver2)
                        <li class="list-group-item border-light-subtle px-4 py-3 d-flex justify-content-between align-items-start gap-2">
                            <span class="text-body-secondary small">Approved by A2</span>
                            <span class="fw-semibold text-success text-end">{{ $request->approver2->name ?? '--' }}</span>
                        </li>
                        @endif
                        @if($request->rejection_reason)
                        <li class="list-group-item border-light-subtle px-4 py-3 d-flex justify-content-between align-items-start gap-2">
                            <span class="text-body-secondary small">Rejection reason</span>
                            <span class="fw-semibold text-danger small text-end">{{ Str::limit($request->rejection_reason, 30) }}</span>
                        </li>
                        @endif
                        <li class="list-group-item border-0 px-4 py-3 d-flex justify-content-between align-items-center bg-body-tertiary">
                            <span class="text-body-secondary small fw-semibold">Status</span>
                            <span class="badge bg-{{ $statusClass }} rounded-pill px-3"
                                  @if(($request->status ?? '') === 'Approved') title="Please collect your ID card from security section" @endif>{{ $request->status }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4 d-flex align-items-center gap-2">
                    <span class="rounded-2 bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center">
                        <i class="material-icons material-symbols-rounded fs-5 lh-1">manage_accounts</i>
                    </span>
                    <h2 class="h6 mb-0 fw-bold text-body-emphasis">Actions</h2>
                </div>
                <div class="card-body p-4">
                    <div class="vstack gap-2">
                        @if($request->user_may_edit_request ?? false)
                        <a href="{{ route('admin.employee_idcard.edit', $request->id) }}" class="btn btn-primary rounded-3 d-inline-flex align-items-center justify-content-center gap-2 py-2">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1">edit</i>
                            Edit Request
                        </a>
                        @endif
                        <a href="{{ route('admin.employee_idcard.index') }}" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center justify-content-center gap-2 py-2">
                            <i class="material-icons material-symbols-rounded fs-6 lh-1">arrow_back</i>
                            Back to List
                        </a>
                        @if($request->user_may_edit_request ?? false)
                        <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}" method="POST" class="mt-1" onsubmit="return confirm('Are you sure you want to delete this request?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger rounded-3 w-100 d-inline-flex align-items-center justify-content-center gap-2 py-2">
                                <i class="material-icons material-symbols-rounded fs-6 lh-1">delete</i>
                                Delete Request
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection
