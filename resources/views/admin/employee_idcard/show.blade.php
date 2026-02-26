@extends('admin.layouts.master')
@section('title', 'View ID Card Request - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Employee ID Card Request Details"></x-breadcrum>
    <x-session_message />
    <!-- Header Card - Bootstrap 5.3 -->
    <div class="card border-0 shadow rounded-3 mb-4 overflow-hidden" style="border-left: 4px solid #004a93;">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 fw-bold text-dark">
                        <i class="material-icons material-symbols-rounded align-middle me-2" style="font-size:28px;">visibility</i>
                        Employee ID Card Request Details
                    </h4>
                    <p class="text-muted mb-0">Request ID: <strong>#{{ $request->id }}</strong> | Created: <strong>{{ $request->created_at ? $request->created_at->format('d/m/Y H:i') : '--' }}</strong></p>
                </div>
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
                <div class="text-end">
                    <h6 class="mb-2">Current Status</h6>
                    <span class="badge bg-{{ $statusClass }} p-2">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;">{{ $statusIcon }}</i>
                        {{ $request->status }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Employee Type - Bootstrap 5.3 -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">person_badge</i>
                        Employee Type
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Type</small>
                                <strong class="text-dark d-block fs-6">{{ $request->employee_type }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Card Type</small>
                                <strong class="text-dark d-block fs-6">{{ $request->card_type ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Sub Type</small>
                                <strong class="text-dark d-block fs-6">{{ $request->sub_type ?? '--' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information - Bootstrap 5.3 -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">info</i>
                        Personal Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Full Name</small>
                                <strong class="text-dark d-block fs-6">{{ $request->name }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Designation</small>
                                <strong class="text-dark d-block fs-6">{{ $request->designation ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Date of Birth</small>
                                <strong class="text-dark d-block fs-6">
                                    @if($request->date_of_birth)
                                        {{ \Carbon\Carbon::parse($request->date_of_birth)->format('d M, Y') }}
                                    @else
                                        --
                                    @endif
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Father Name</small>
                                <strong class="text-dark d-block fs-6">{{ $request->father_name ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Academy Joining Date</small>
                                <strong class="text-dark d-block fs-6">
                                    @if($request->academy_joining)
                                        {{ \Carbon\Carbon::parse($request->academy_joining)->format('d M, Y') }}
                                    @else
                                        --
                                    @endif
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Blood Group</small>
                                <strong class="text-danger d-block fs-6">{{ $request->blood_group ?? '--' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information - Bootstrap 5.3 -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">phone</i>
                        Contact Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Mobile Number</small>
                                <strong class="text-dark d-block fs-6">{{ $request->mobile_number ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Telephone Number</small>
                                <strong class="text-dark d-block fs-6">{{ $request->telephone_number ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Section</small>
                                <strong class="text-dark d-block fs-6">{{ $request->section ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">ID Card Valid Upto</small>
                                <strong class="text-dark d-block fs-6">{{ $request->id_card_valid_upto ?? '--' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Details - Bootstrap 5.3 -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">domain</i>
                        Additional Details
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Approval Authority</small>
                                <strong class="text-dark d-block fs-6">{{ $request->approval_authority ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Vendor / Organization</small>
                                <strong class="text-dark d-block fs-6">{{ $request->vendor_organization_name ?? '--' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Request For</small>
                                <strong class="text-dark d-block fs-6">{{ $request->request_for ?? '--' }}</strong>
                            </div>
                        </div>
                        @if(in_array($request->request_for, ['Replacement', 'Duplication']) && $request->duplication_reason)
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Duplication</small>
                                @php
                                    $dupBadge = match($request->duplication_reason) {
                                        'Lost' => 'danger',
                                        'Damage' => 'warning',
                                        'Expired Card' => 'info',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $dupBadge }} text-dark">{{ $request->duplication_reason }}</span>
                            </div>
                        </div>
                        @endif
                        @if($request->id_card_number)
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">ID Card Number</small>
                                <strong class="text-dark d-block fs-6">{{ $request->id_card_number }}</strong>
                            </div>
                        </div>
                        @endif
                        @if($request->id_card_valid_from)
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">ID Card Valid From</small>
                                <strong class="text-dark d-block fs-6">{{ $request->id_card_valid_from }}</strong>
                            </div>
                        </div>
                        @endif
                        @if($request->request_for == 'Extension')
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Extension</small>
                                <strong class="text-dark d-block fs-6">{{ $request->id_card_valid_upto ?? '--' }}</strong>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-2">
                                <small class="text-muted d-block mb-1">Request Date</small>
                                <strong class="text-dark d-block fs-6">{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            @if($request->remarks)
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-light border-0 rounded-top-3 p-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="material-icons material-symbols-rounded align-middle me-2">comment</i>
                            Remarks
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-dark mb-0">{{ $request->remarks }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Documents Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">attachment</i>
                        Attached Documents
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <!-- Photo -->
                        @if($request->photo)
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-primary" style="font-size:48px;">image</i>
                                    <small class="d-block mt-2 text-muted">Photo</small>
                                    <a href="{{ asset('storage/' . $request->photo) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2 w-100">
                                        <i class="material-icons material-symbols-rounded align-middle" style="font-size:16px;">download</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-muted" style="font-size:48px; opacity: 0.5;">image</i>
                                    <small class="d-block mt-2 text-muted">No photo uploaded</small>
                                </div>
                            </div>
                        @endif

                        <!-- Joining Letter -->
                        @if($request->joining_letter)
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-info" style="font-size:48px;">description</i>
                                    <small class="d-block mt-2 text-muted">Joining Letter</small>
                                    <a href="{{ asset('storage/' . $request->joining_letter) }}" target="_blank" class="btn btn-sm btn-outline-info mt-2 w-100">
                                        <i class="material-icons material-symbols-rounded align-middle" style="font-size:16px;">visibility</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- FIR Receipt (for Lost/Damaged) -->
                        @if($request->fir_receipt)
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-warning" style="font-size:48px;">gavel</i>
                                    <small class="d-block mt-2 text-muted">FIR Receipt</small>
                                    <a href="{{ asset('storage/' . $request->fir_receipt) }}" target="_blank" class="btn btn-sm btn-outline-warning mt-2 w-100">
                                        <i class="material-icons material-symbols-rounded align-middle" style="font-size:16px;">visibility</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Payment Receipt -->
                        @if($request->payment_receipt)
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-success" style="font-size:48px;">receipt</i>
                                    <small class="d-block mt-2 text-muted">Payment Receipt</small>
                                    <a href="{{ asset('storage/' . $request->payment_receipt) }}" target="_blank" class="btn btn-sm btn-outline-success mt-2 w-100">
                                        <i class="material-icons material-symbols-rounded align-middle" style="font-size:16px;">visibility</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Documents -->
                        @if($request->documents)
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-success" style="font-size:48px;">description</i>
                                    <small class="d-block mt-2 text-muted">Documents</small>
                                    <a href="{{ asset('storage/' . $request->documents) }}" target="_blank" class="btn btn-sm btn-outline-success mt-2 w-100">
                                        <i class="material-icons material-symbols-rounded align-middle" style="font-size:16px;">download</i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        @elseif($request->joining_letter)
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-success" style="font-size:48px;">description</i>
                                    <small class="d-block mt-2 text-muted">Joining Letter / Document</small>
                                    <a href="{{ asset('storage/' . $request->joining_letter) }}" target="_blank" class="btn btn-sm btn-outline-success mt-2 w-100">
                                        <i class="material-icons material-symbols-rounded align-middle" style="font-size:16px;">download</i>
                                        View / Download
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="card border-0 bg-light rounded-2 p-3 text-center">
                                    <i class="material-icons material-symbols-rounded text-muted" style="font-size:48px; opacity: 0.5;">description</i>
                                    <small class="d-block mt-2 text-muted">No documents uploaded</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Info Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">info_outline</i>
                        Quick Info
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Created By:</span>
                        <strong class="text-dark">{{ $request->created_by ?? '--' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Last Updated:</span>
                        <strong class="text-dark">{{ $request->updated_at->diffForHumans() }}</strong>
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
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Status:</span>
                        <span class="badge bg-{{ $statusClass }}">{{ $request->status }}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons - Bootstrap 5.3 -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-light border-0 rounded-top-3 p-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="material-icons material-symbols-rounded align-middle me-2">manage_accounts</i>
                        Actions
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.employee_idcard.edit', $request->id) }}" class="btn btn-primary rounded-2">
                            <i class="material-icons material-symbols-rounded align-middle me-2">edit</i>
                            Edit Request
                        </a>
                        <a href="{{ route('admin.employee_idcard.index') }}" class="btn btn-outline-secondary rounded-2">
                            <i class="material-icons material-symbols-rounded align-middle me-2">arrow_back</i>
                            Back to List
                        </a>
                        <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}" method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to delete this request?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger rounded-2 w-100">
                                <i class="material-icons material-symbols-rounded align-middle me-2">delete</i>
                                Delete Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light {
        background-color: #f8f9fa !important;
    }

    .card {
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }

    .badge {
        font-weight: 500;
        padding: 0.4rem 0.8rem;
        border-radius: 0.35rem;
    }
</style>
@endsection
