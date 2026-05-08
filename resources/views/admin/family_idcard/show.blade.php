@extends('admin.layouts.master')
@section('title', 'Family ID Card Request Details - Sargam')
@section('content')
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
                    @if($can_modify_request ?? false)
                        <a href="{{ route('admin.family_idcard.edit', $request->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                    @endif
                    <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><strong>Employee ID:</strong> {{ $request->employee_id ?? '--' }}</div>
                <div class="col-md-6"><strong>Employee Name:</strong> {{ $request->employee_name ?? '--' }}</div>
                <div class="col-md-6"><strong>Designation:</strong> {{ $request->guardian_designation ?? $request->designation ?? '--' }}</div>
                <div class="col-md-6"><strong>Card Type:</strong> {{ $request->card_type ?? 'Family' }}</div>
                <div class="col-md-6"><strong>Section:</strong> {{ $request->section ?? '--' }}</div>
                <div class="col-md-6"><strong>Valid From:</strong> {{ $request->valid_from ? $request->valid_from->format('d/m/Y') : '--' }}</div>
                <div class="col-md-6"><strong>Valid To:</strong> {{ $request->valid_to ? $request->valid_to->format('d/m/Y') : '--' }}</div>
                <div class="col-md-6"><strong>Status:</strong> <span class="badge bg-primary">{{ $request->status ?? 'Pending' }}</span></div>
                <div class="col-md-6">
                    @php
                        $firstMember = isset($members) && count($members) ? $members->first() : null;
                        $groupPhotoPath = $firstMember->family_photo ?? $request->family_photo ?? null;
                        $groupPhotoExists = $groupPhotoPath && \Storage::disk('public')->exists($groupPhotoPath);
                    @endphp
                    @if($groupPhotoExists)
                        <strong>Group Photo:</strong><br>
                        <img src="{{ asset('storage/' . $groupPhotoPath) }}" alt="Group Photo" class="img-thumbnail mt-1" style="max-height: 200px;">
                    @elseif($groupPhotoPath)
                        <strong>Group Photo:</strong> <span class="text-warning small">No file available in storage</span>
                    @else
                        <strong>Group Photo:</strong> --
                    @endif
                </div>
                <div class="col-md-6">
                    @php
                        $individualPath = $request->id_photo_path;
                        $individualExists = $individualPath && \Storage::disk('public')->exists($individualPath);
                    @endphp
                    @if($individualExists)
                        <strong>Individual Photo (selected member):</strong><br>
                        <img src="{{ asset('storage/' . $individualPath) }}" alt="Individual Photo" class="img-thumbnail mt-1" style="max-height: 200px;">
                    @elseif($individualPath)
                        <strong>Individual Photo (selected member):</strong> <span class="text-warning small">No file available in storage</span>
                    @else
                        <strong>Individual Photo (selected member):</strong> --
                    @endif
                </div>
                @if(isset($request->remarks) && $request->remarks)
                    <div class="col-12"><strong>Remarks:</strong> {{ $request->remarks }}</div>
                @endif
            @if(isset($members) && $members->count())
                <hr class="my-4">
                <h6 class="fw-semibold mb-3">Family Members List</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;">S.No.</th>
                                <th>Member Name</th>
                                <th>Relation</th>
                                <th>DOB</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                                <th style="width:140px;">Individual Photo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $index => $member)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $member->name ?? '--' }}</td>
                                    <td>{{ $member->relation ?? '--' }}</td>
                                    <td>{{ $member->dob ? \App\Support\IdCardSecurityMapper::formatDateForDisplay($member->dob) : '--' }}</td>
                                    <td>{{ $member->valid_from ? \App\Support\IdCardSecurityMapper::formatDateForDisplay($member->valid_from) : '--' }}</td>
                                    <td>{{ $member->valid_to ? \App\Support\IdCardSecurityMapper::formatDateForDisplay($member->valid_to) : '--' }}</td>
                                    <td class="text-center">
                                        @php
                                            $photo = $member->id_photo_path ?? $member->family_photo ?? null;
                                            $photoExists = $photo && \Storage::disk('public')->exists($photo);
                                        @endphp
                                        @if($photoExists)
                                            <img src="{{ asset('storage/' . $photo) }}" alt="Member Photo" class="img-thumbnail" style="max-height:80px;">
                                        @elseif($photo)
                                            <span class="text-warning small">No file available in storage</span>
                                        @else
                                            --
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        </div>
    </div>
</div>
@endsection
