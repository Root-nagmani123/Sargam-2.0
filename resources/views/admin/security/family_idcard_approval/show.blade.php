@extends('admin.layouts.master')
@section('title', 'Family ID Card - Approval Review')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Family ID Card - Approval Review</h4>
                    <small class="text-muted">Request ID: <code>{{ $application->fml_id_apply }}</code></small>
                </div>
                <div>
                    <a href="{{ route('admin.security.family_idcard_approval.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                        Back to Pending
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert" style="background-color: #f0f7ff; border-left: 4px solid #0066cc;">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Status:</strong><br>
                                @php
                                    $statusClass = match((int)$application->id_status) {
                                        1 => 'warning',
                                        2 => 'success',
                                        3 => 'danger',
                                        default => 'secondary',
                                    };
                                    $statusText = $request->status_label ?? 'Pending';
                                @endphp
                                <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Submitted Date:</strong><br>
                                {{ $application->created_date ? $application->created_date->format('d-M-Y H:i') : '--' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Card Type:</strong><br>
                                Family ID Card
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">Family Member Details</h5>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Family Member Name</label>
                    <div class="form-control bg-light"><strong>{{ $application->family_name ?? '--' }}</strong></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Relation</label>
                    <div class="form-control bg-light">{{ $application->family_relation ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Employee ID (Applicant)</label>
                    <div class="form-control bg-light">{{ $application->emp_id_apply ?? '--' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Date of Birth</label>
                    <div class="form-control bg-light">
                        {{ $application->employee_dob ? $application->employee_dob->format('d-M-Y') : '--' }}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Valid From</label>
                    <div class="form-control bg-light">
                        {{ $application->card_valid_from ? $application->card_valid_from->format('d-M-Y') : '--' }}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Valid To</label>
                    <div class="form-control bg-light">
                        {{ $application->card_valid_to ? $application->card_valid_to->format('d-M-Y') : '--' }}
                    </div>
                </div>
            </div>

            @if($application->family_photo || $application->id_photo_path)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">Photo</h5>
                        @php $photoPath = $application->family_photo ?? $application->id_photo_path; @endphp
                        @if($photoPath)
                            <img src="{{ asset('storage/' . $photoPath) }}" alt="Family Photo" class="img-thumbnail" style="max-height: 200px;">
                        @endif
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
                                    <tr><th>Status</th><th>Remarks</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($application->approvals as $approval)
                                        <tr>
                                            <td>
                                                <span class="badge {{ ($approval->status ?? 0) == 1 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ ($approval->status ?? 0) == 1 ? 'Approved' : 'Rejected' }}
                                                </span>
                                            </td>
                                            <td><small>{{ $approval->approval_remarks ?? '--' }}</small></td>
                                            <td><small>{{ $approval->created_date ? $approval->created_date->format('d-M-Y H:i') : '--' }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if($application->id_status == 1)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">Approval Actions</h5>
                        <div class="d-flex gap-3 flex-wrap">
                            <form action="{{ route('admin.security.family_idcard_approval.approve', encrypt($application->fml_id_apply)) }}" method="POST" class="d-inline">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Remarks (Optional)</label>
                                    <textarea name="approval_remarks" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this Family ID Card?')">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">check</i>
                                    Approve
                                </button>
                            </form>
                            <form action="{{ route('admin.security.family_idcard_approval.reject', encrypt($application->fml_id_apply)) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this Family ID Card?')">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="approval_remarks" class="form-control" rows="2" required></textarea>
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
                <a href="{{ route('admin.security.family_idcard_approval.index') }}" class="btn btn-secondary">Close</a>
                <a href="{{ route('admin.family_idcard.members', $application->fml_id_apply) }}" class="btn btn-outline-primary">View All Members in Group</a>
            </div>
        </div>
    </div>
</div>
@endsection
