@extends('admin.layouts.master')
@section('title', 'Duplicate Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Duplicate Vehicle Pass Request"></x-breadcrum>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                <h5 class="mb-0">Request #{{ $req->id }}</h5>
                <span class="badge {{ $req->status === 'Approved' ? 'bg-success' : ($req->status === 'Rejected' ? 'bg-danger' : ($req->status === 'Issued' ? 'bg-info' : 'bg-warning text-dark')) }}">{{ $req->status }}</span>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><strong>Vehicle Number:</strong> {{ $req->vehicle_number ?? '--' }}</div>
                <div class="col-md-6"><strong>Vehicle Pass No:</strong> {{ $req->vehicle_pass_no ?? '--' }}</div>
                <div class="col-md-6"><strong>Id Card Number:</strong> {{ $req->id_card_number ?? '--' }}</div>
                <div class="col-md-6"><strong>Employee Name:</strong> {{ $req->employee_name ?? '--' }}</div>
                <div class="col-md-6"><strong>Designation:</strong> {{ $req->designation ?? '--' }}</div>
                <div class="col-md-6"><strong>Department:</strong> {{ $req->department ?? '--' }}</div>
                <div class="col-md-6"><strong>Vehicle Type:</strong> {{ $req->vehicleType->vehicle_type ?? '--' }}</div>
                <div class="col-md-6"><strong>Start Date:</strong> {{ $req->start_date ? $req->start_date->format('d-m-Y') : '--' }}</div>
                <div class="col-md-6"><strong>End Date:</strong> {{ $req->end_date ? $req->end_date->format('d-m-Y') : '--' }}</div>
                <div class="col-md-6"><strong>Reason For Duplicate:</strong> {{ $req->reason_for_duplicate ?? '--' }}</div>
                <div class="col-md-6"><strong>Request Date:</strong> {{ $req->created_at ? $req->created_at->format('d-m-Y H:i') : '--' }}</div>
                <div class="col-12">
                    <strong>Uploaded Document:</strong>
                    @if($req->doc_upload)
                        <a href="{{ asset('storage/' . $req->doc_upload) }}" target="_blank" class="ms-2">Download</a>
                    @else
                        <span class="text-muted">--</span>
                    @endif
                </div>
                @if($req->remarks)
                    <div class="col-12"><strong>Remarks:</strong> {{ $req->remarks }}</div>
                @endif
            </div>
            <div class="d-flex gap-2 mt-4">
                <a href="{{ route('admin.security.duplicate_vehicle_pass.edit', $req->id) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('admin.security.duplicate_vehicle_pass.index') }}" class="btn btn-outline-secondary">Back to List</a>
            </div>
        </div>
    </div>
</div>
@endsection
