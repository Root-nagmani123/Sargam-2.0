@extends('admin.layouts.master')
@section('title', 'Visitor Pass Details - {{ $visitorPass->pass_number ?? "N/A" }}')
@section('setup_content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Visitor Pass Details</h4>
                    <small class="text-muted">Pass Number: <code>{{ $visitorPass->pass_number }}</code></small>
                </div>
                <div>
                    <a href="{{ route('admin.security.visitor_pass.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                        Back to List
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Pass Status -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert" style="background-color: #f0f7ff; border-left: 4px solid #0066cc;">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Issued Date:</strong><br>
                                {{ $visitorPass->issued_date ? \Carbon\Carbon::parse($visitorPass->issued_date)->format('d-M-Y') : '--' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Valid For Days:</strong><br>
                                {{ $visitorPass->valid_for_days }} day(s)
                            </div>
                            <div class="col-md-4">
                                <strong>In Time:</strong><br>
                                {{ $visitorPass->in_time ? \Carbon\Carbon::parse($visitorPass->in_time)->format('d-M-Y H:i') : '--' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visitor Details -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">person</i>
                        Visitor Details
                    </h5>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Visitor Name(s)</label>
                        <div class="form-control bg-light">
                            @if($visitorPass->visitorNames && count($visitorPass->visitorNames) > 0)
                                <ul class="mb-0">
                                    @foreach($visitorPass->visitorNames as $name)
                                        <li>{{ $name->visitor_name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mobile Number</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->mobile_number }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company/Organization</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->company ?? '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <div class="form-control bg-light" style="min-height: 60px; overflow-y: auto;">
                            {{ $visitorPass->address ?? '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Identity Card Type</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->identity_card ?? '--' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ID Number</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->id_no ?? '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Details -->
            <div class="row mb-3 mt-3">
                <div class="col-md-12">
                    <h5 class="text-primary mb-3">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">meeting_room</i>
                        Visit Details
                    </h5>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Whom to Meet</label>
                        <div class="form-control bg-light">
                            @if($visitorPass->employee)
                                <strong>{{ $visitorPass->employee->emp_name }}</strong><br>
                                <small class="text-muted">{{ $visitorPass->employee->emp_code ?? 'N/A' }}</small>
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Purpose of Visit</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->purpose }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">In Time</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->in_time ? \Carbon\Carbon::parse($visitorPass->in_time)->format('d-M-Y H:i') : '--' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Out Time</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->out_time ? \Carbon\Carbon::parse($visitorPass->out_time)->format('d-M-Y H:i') : 'Still inside' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Number</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->vehicle_number ?? '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachment -->
            @if($visitorPass->upload_path)
                <div class="row mb-3 mt-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">attach_file</i>
                            Attached Document
                        </h5>
                        <a href="{{ Storage::url($visitorPass->upload_path) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">download</i>
                            Download Document
                        </a>
                    </div>
                </div>
            @endif

            <!-- Created By -->
            <div class="row mb-3 mt-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Created By</label>
                        <div class="form-control bg-light">
                            @if($visitorPass->createdBy)
                                {{ $visitorPass->createdBy->emp_name ?? 'N/A' }}
                            @else
                                <span class="text-muted">--</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Created Date</label>
                        <div class="form-control bg-light">
                            {{ $visitorPass->created_date ? $visitorPass->created_date->format('d-M-Y H:i') : '--' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <a href="{{ route('admin.security.visitor_pass.edit', encrypt($visitorPass->pk)) }}" class="btn btn-warning">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">edit</i>
                        Edit Visitor Pass
                    </a>
                    <form action="{{ route('admin.security.visitor_pass.delete', encrypt($visitorPass->pk)) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this visitor pass?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">delete</i>
                            Delete Visitor Pass
                        </button>
                    </form>
                    <a href="{{ route('admin.security.visitor_pass.index') }}" class="btn btn-secondary">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">close</i>
                        Close
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
