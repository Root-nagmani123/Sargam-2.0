@extends('admin.layouts.master')
@section('title', 'Family ID Card Request Details - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Family ID Card Request Details"></x-breadcrum>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold">Request Details</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.family_idcard.edit', $request->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                    <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><strong>Employee ID:</strong> {{ $request->employee_id ?? '--' }}</div>
                <div class="col-md-6"><strong>Employee Name:</strong> {{ $request->employee_name ?? '--' }}</div>
                <div class="col-md-6"><strong>Designation:</strong> {{ $request->designation ?? '--' }}</div>
                <div class="col-md-6"><strong>Card Type:</strong> {{ $request->card_type ?? '--' }}</div>
                <div class="col-md-6"><strong>Family Member Name:</strong> {{ $request->name ?? '--' }}</div>
                <div class="col-md-6"><strong>Relation:</strong> {{ $request->relation ?? '--' }}</div>
                <div class="col-md-6"><strong>Family Member ID:</strong> {{ $request->family_member_id ?? '--' }}</div>
                <div class="col-md-6"><strong>Section:</strong> {{ $request->section ?? '--' }}</div>
                <div class="col-md-6"><strong>DOB:</strong> {{ $request->dob ? $request->dob->format('d/m/Y') : '--' }}</div>
                <div class="col-md-6"><strong>Valid From:</strong> {{ $request->valid_from ? $request->valid_from->format('d/m/Y') : '--' }}</div>
                <div class="col-md-6"><strong>Valid To:</strong> {{ $request->valid_to ? $request->valid_to->format('d/m/Y') : '--' }}</div>
                <div class="col-md-6"><strong>Status:</strong> <span class="badge bg-primary">{{ $request->status ?? 'Pending' }}</span></div>
                <div class="col-md-6">
                    @if($request->group_photo)
                        <strong>Group Photo:</strong><br>
                        <img src="{{ asset('storage/' . $request->group_photo) }}" alt="Group Photo" class="img-thumbnail mt-1" style="max-height: 200px;">
                    @else
                        <strong>Group Photo:</strong> --
                    @endif
                </div>
                <div class="col-md-6">
                    @if($request->family_photo)
                        <strong>Individual Photo:</strong><br>
                        <img src="{{ asset('storage/' . $request->family_photo) }}" alt="Individual Photo" class="img-thumbnail mt-1" style="max-height: 200px;">
                    @else
                        <strong>Individual Photo:</strong> --
                    @endif
                </div>
                @if(isset($request->remarks) && $request->remarks)
                    <div class="col-12"><strong>Remarks:</strong> {{ $request->remarks }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
