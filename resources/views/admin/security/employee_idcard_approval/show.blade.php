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
               
            </div>

            <div class="row">
                <div class="col-md-6">
                    {{-- Photo + Documents --}}
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <h6 class="card-title mb-3">Employee Photo</h6>
                                @php
                                    $photoPath = null;
                                    $photoExists = false;
                                    if ($request->photo) {
                                        $photoPath = str_starts_with($request->photo, 'idcard/')
                                            ? $request->photo
                                            : 'idcard/photos/' . $request->photo;
                                        $photoExists = \Storage::disk('public')->exists($photoPath);
                                    }
                                @endphp
                                @if($photoExists)
                                    <img src="{{ asset('storage/' . $photoPath) }}" alt="Employee Photo" class="img-fluid rounded" style="max-height: 250px; object-fit: cover; border: 1px solid #dee2e6;">
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="material-icons material-symbols-rounded" style="font-size:16px;">download</i>
                                            Download
                                        </a>
                                    </div>
                                @elseif($request->photo)
                                    <img src="{{ asset('images/dummypic.jpeg') }}" alt="No Photo" class="img-fluid rounded" style="max-height: 250px; object-fit: cover; border: 1px solid #dee2e6;">
                                    <p class="text-warning small mt-2">No file available in storage</p>
                                @else
                                    <img src="{{ asset('images/dummypic.jpeg') }}" alt="No Photo" class="img-fluid rounded" style="max-height: 250px; object-fit: cover; border: 1px solid #dee2e6;">
                                    <p class="text-muted small mt-2">No photo available</p>
                                @endif
                            </div>

                            @php
                                $docLinks = [];
                                // Joining letter / extension docs (regular permanent/contractual)
                                if (!empty($request->joining_letter)) {
                                    $docLinks[] = [
                                        'label' => 'Joining Letter',
                                        'path' => 'idcard/' . ltrim($request->joining_letter, '/'),
                                        'full' => false,
                                    ];
                                }
                                if (!empty($request->extension_document_path)) {
                                    $docLinks[] = [
                                        'label' => 'Extension Document',
                                        'path' => ltrim($request->extension_document_path, '/'),
                                        'full' => false,
                                    ];
                                }
                                // Duplicate-specific docs (Card Lost / Damage / Service Extended / etc.)
                                if (!empty($request->fir_receipt)) {
                                    $docLinks[] = [
                                        'label' => 'FIR Copy / Document Proof',
                                        'path' => 'idcard/dup_docs/' . ltrim($request->fir_receipt, '/'),
                                        'full' => false,
                                    ];
                                }
                                if (!empty($request->payment_receipt)) {
                                    $docLinks[] = [
                                        'label' => 'Payment Receipt',
                                        'path' => 'idcard/dup_docs/' . ltrim($request->payment_receipt, '/'),
                                        'full' => false,
                                    ];
                                }
                                if (!empty($request->service_ext)) {
                                    $docLinks[] = [
                                        'label' => 'Service Extension / Renewal Proof',
                                        'path' => 'idcard/dup_docs/' . ltrim($request->service_ext, '/'),
                                        'full' => false,
                                    ];
                                }
                                if (!empty($request->id_proof_doc)) {
                                    $docLinks[] = [
                                        'label' => 'ID Proof (Aadhar / Other)',
                                        'path' => 'idcard/dup_docs/' . ltrim($request->id_proof_doc, '/'),
                                        'full' => false,
                                    ];
                                }
                                if (!empty($request->documents)) {
                                    $docLinks[] = [
                                        'label' => 'Supporting Document',
                                        'path' => ltrim($request->documents, '/'),
                                        'full' => false,
                                    ];
                                }
                                if (!empty($request->other_documents)) {
                                    $docLinks[] = [
                                        'label' => 'Other Document',
                                        'path' => 'idcard/dup_docs/' . ltrim($request->other_documents, '/'),
                                        'full' => false,
                                    ];
                                }
                            @endphp

                            @if(!empty($docLinks))
                                <hr>
                                <h6 class="card-title mb-2">Uploaded Documents</h6>
                                <ul class="list-unstyled mb-0 small text-start">
                                    @foreach($docLinks as $doc)
                                        @php
                                            $p = $doc['path'] ?? null;
                                            $exists = $p && \Storage::disk('public')->exists($p);
                                        @endphp
                                        <li class="mb-1">
                                            <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">attach_file</i>
                                            @if($exists)
                                                <a href="{{ asset('storage/' . $p) }}" target="_blank">
                                                    {{ $doc['label'] }}
                                                </a>
                                            @else
                                                <span class="text-warning small">{{ $doc['label'] }} - No file available in storage</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                   
                            <table class="table table-bordered table-sm">
                                <tr><th width="40%">Employee Name</th><td>{{ $request->name }}</td></tr>
                                <tr><th>Father Name</th><td>{{ $request->father_name ?? '--' }}</td></tr>
                                <tr><th>Designation</th><td>{{ $request->designation ?? '--' }}</td></tr>
                                <tr><th>Employee Type</th><td>{{ $request->employee_type ?? ($request->card_type ?? '--') }}</td></tr>
                                <tr><th>Card Type (ID Type)</th><td>{{ $request->card_type ?? '--' }}</td></tr>
                                <tr><th>ID Card No</th><td>{{ $request->id_card_number ?? '--' }}</td></tr>
                                <tr><th>Request For</th><td>{{ $request->request_for ?? '--' }}</td></tr>
                                <tr><th>Request Type</th>
                                    <td>
                                        @if(isset($request->request_type) && $request->request_type === 'duplicate')
                                            <span class="badge bg-info">Duplicate</span>
                                        @else
                                            <span class="badge bg-secondary">Regular</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>Date of Birth</th>
                                    <td>
                                        @if(!empty($request->date_of_birth))
                                            {{ \Carbon\Carbon::parse($request->date_of_birth)->format('d/m/Y') }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>Blood Group</th><td>{{ $request->blood_group ?? '--' }}</td></tr>
                                <tr><th>Contact No</th><td>{{ $request->mobile_number ?? $request->telephone_number ?? '--' }}</td></tr>
                                <tr><th>Valid From</th><td>{{ $request->id_card_valid_from ?? '--' }}</td></tr>
                                <tr><th>Valid Upto</th><td>{{ $request->id_card_valid_upto ?? '--' }}</td></tr>
                                <tr><th>Request Date</th><td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td></tr>
                                <tr><th>Requested By</th><td>{{ $request->requested_by ?? '--' }}</td></tr>
                                <tr><th>Requested Section</th><td>{{ $request->requested_section ?? '--' }}</td></tr>
                                @if(!empty($request->card_reason))
                                    <tr><th>Duplicate Reason</th><td>{{ $request->card_reason }}</td></tr>
                                @elseif(!empty($request->duplication_reason))
                                    <tr><th>Duplicate Reason</th><td>{{ $request->duplication_reason }}</td></tr>
                                @endif
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
                        
                    
            </div>

          
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
