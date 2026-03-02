@extends('admin.layouts.master')
@section('title', 'ID Card Approval Details')
@section('setup_content')
<style>
    .table th{
        background-color: #004a93;
        color: #fff;
    }
</style>
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'ID Card Approval Details'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Request #{{ $request->id }} - {{ $request->name }}</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.employee_idcard.show', $request->id) }}" class="btn btn-outline-primary btn-sm">Full Details</a>
                    <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}" class="btn btn-secondary btn-sm">Back to Approval I</a>
                    <a href="{{ route('admin.security.employee_idcard_approval.approval2') }}" class="btn btn-secondary btn-sm">Back to Approval II</a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    {{-- Photo Display --}}
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body text-center">
                            <h6 class="card-title mb-3">Employee Photo</h6>
                            @if($request->photo)
                                @php
                                    $photoPath = str_starts_with($request->photo, 'idcard/')
                                        ? $request->photo
                                        : 'idcard/photos/' . $request->photo;
                                    $photoExists = \Storage::disk('public')->exists($photoPath);
                                    $photoUrl = $photoExists ? asset('storage/' . $photoPath) : asset('images/dummypic.jpeg');
                                @endphp
                                <img src="{{ $photoUrl }}" alt="Employee Photo" class="img-fluid rounded" style="max-height: 250px; object-fit: cover; border: 1px solid #dee2e6;">
                                <div class="mt-2">
                                    <a href="{{ $photoUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="material-icons material-symbols-rounded" style="font-size:16px;">download</i>
                                        Download
                                    </a>
                                </div>
                            @else
                                <img src="{{ asset('images/dummypic.jpeg') }}" alt="No Photo" class="img-fluid rounded" style="max-height: 250px; object-fit: cover; border: 1px solid #dee2e6;">
                                <p class="text-muted small mt-2">No photo available</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th width="40%">Employee Name</th><td>{{ $request->name }}</td></tr>
                                <tr><th>Designation</th><td>{{ $request->designation ?? '--' }}</td></tr>
                                <tr><th>Card Type</th><td>{{ $request->card_type ?? '--' }}</td></tr>
                                <tr><th>Request For</th><td>{{ $request->request_for ?? '--' }}</td></tr>
                                <tr><th>Request Date</th><td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td></tr>
                                <tr><th>Status</th>
                                    <td>
                                        @php
                                            $statusClass = match($request->status) {
                                                'Pending' => 'warning',
                                                'Approved' => 'success',
                                                'Rejected' => 'danger',
                                                'Issued' => 'primary',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ $request->status }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th width="40%">Approved By A1</th>
                                    <td>
                                        @if($request->approver1)
                                            {{ $request->approver1->name }}
                                            @if($request->approved_by_a1_at)
                                                <br><small class="text-muted">{{ $request->approved_by_a1_at->format('d/m/Y H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">--</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>Approved By A2</th>
                                    <td>
                                        @if($request->approver2)
                                            {{ $request->approver2->name }}
                                            @if($request->approved_by_a2_at)
                                                <br><small class="text-muted">{{ $request->approved_by_a2_at->format('d/m/Y H:i') }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">--</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($request->rejection_reason)
                                <tr><th>Rejection Reason</th><td class="text-danger">{{ $request->rejection_reason }}</td></tr>
                                <tr><th>Rejected By</th><td>{{ $request->rejectedByUser?->name ?? '--' }}</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if($request->approved_by_a1 === null && $request->rejected_by === null && $request->status === 'Pending')
                <div class="mt-3 d-flex gap-2">
                    <form action="{{ route('admin.security.employee_idcard_approval.approve1', encrypt($request->id)) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Approve (Move to Approval II)</button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                </div>
            @elseif($request->approved_by_a1 !== null && $request->approved_by_a2 === null && $request->rejected_by === null && $request->status === 'Pending')
                <div class="mt-3 d-flex gap-2">
                    <form action="{{ route('admin.security.employee_idcard_approval.approve2', encrypt($request->id)) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Approve (Final)</button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                </div>
            @endif
        </div>
    </div>
</div>

@if(($request->approved_by_a1 === null && $request->rejected_by === null) || ($request->approved_by_a2 === null && $request->rejected_by === null && $request->approved_by_a1 !== null))
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ $request->approved_by_a1 === null ? route('admin.security.employee_idcard_approval.reject1', encrypt($request->id)) : route('admin.security.employee_idcard_approval.reject2', encrypt($request->id)) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Enter Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
