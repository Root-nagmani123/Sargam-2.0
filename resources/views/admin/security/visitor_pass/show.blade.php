@extends('admin.layouts.master')

@section('title', 'Visitor Pass Details - ' . ($visitorPass->pass_number ?? 'N/A'))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('content')
<div class="container-fluid visitor-pass-show-page">
    <x-breadcrum title="Visitor Pass Details">
    </x-breadcrum>

    <x-session_message />

    @php
        // employee_master has no emp_name / emp_code columns — build the name from
        // first/middle/last and use emp_id as the code, otherwise both render blank.
        $employeeLabel = static function ($emp) {
            if (! $emp) {
                return null;
            }
            $name = trim(implode(' ', array_filter([
                $emp->first_name ?? null,
                $emp->middle_name ?? null,
                $emp->last_name ?? null,
            ])));

            return $name !== '' ? $name : null;
        };

        $hostName = $employeeLabel($visitorPass->employee);
        $hostCode = $visitorPass->employee->emp_id ?? null;
        $createdByName = $employeeLabel($visitorPass->createdBy);

        $visitors = $visitorPass->visitorNames ?? collect();
        $isInside = ! $visitorPass->out_time;

        $docPath = $visitorPass->upload_path;
        $docExists = $docPath && \Storage::disk('public')->exists($docPath);
    @endphp

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-3 p-lg-4">

            {{-- Header: pass number + presence badge --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h5 class="mb-1 fw-semibold">Pass #{{ $visitorPass->pass_number ?? 'N/A' }}</h5>
                    <p class="text-muted small mb-0">Detailed information for this visitor pass.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($isInside)
                        <span class="badge px-3 py-2 bg-warning text-dark">Still Inside</span>
                    @else
                        <span class="badge px-3 py-2 bg-success">Checked Out</span>
                    @endif
                </div>
            </div>

            {{-- Validity summary --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Issued Date</div>
                    <div class="fw-semibold">{{ $visitorPass->issued_date ? \Carbon\Carbon::parse($visitorPass->issued_date)->format('d-M-Y') : '--' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Valid For</div>
                    <div class="fw-semibold">{{ $visitorPass->valid_for_days ? $visitorPass->valid_for_days . ' day(s)' : '--' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small mb-1">No. of Persons</div>
                    <div class="fw-semibold">{{ $visitorPass->included_no_person ?: '--' }}</div>
                </div>
            </div>

            {{-- Visitor Details --}}
            <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-person" aria-hidden="true"></i> Visitor Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="text-muted small mb-1">Visitor Name(s)</div>
                    @if($visitors->count())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($visitors as $name)
                                <span class="badge rounded-1 bg-light text-dark border">{{ $name->visitor_name }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="fw-semibold">--</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Mobile Number</div>
                    <div class="fw-semibold">{{ $visitorPass->mobile_number ?: '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Company / Organization</div>
                    <div class="fw-semibold">{{ $visitorPass->company ?: '--' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted small mb-1">Address</div>
                    <div class="fw-semibold">{{ $visitorPass->address ?: '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Identity Card Type</div>
                    <div class="fw-semibold">{{ $visitorPass->identity_card ?: '--' }}</div>
                </div>
                <div class="col-md-6">
                    {{-- Column is id_card_no; the old page read id_no, which does not exist. --}}
                    <div class="text-muted small mb-1">ID Number</div>
                    <div class="fw-semibold">{{ $visitorPass->id_card_no ?: '--' }}</div>
                </div>
            </div>

            {{-- Visit Details --}}
            <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-door-open" aria-hidden="true"></i> Visit Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Whom to Meet</div>
                    <div class="fw-semibold">
                        {{ $hostName ?: '--' }}
                        @if($hostCode)
                            <span class="text-muted small">({{ $hostCode }})</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Purpose of Visit</div>
                    <div class="fw-semibold">{{ $visitorPass->purpose ?: '--' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small mb-1">In Time</div>
                    <div class="fw-semibold">{{ $visitorPass->in_time ? $visitorPass->in_time->format('H:i') : '--' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Out Time</div>
                    <div class="fw-semibold">
                        @if($visitorPass->out_time)
                            {{ $visitorPass->out_time->format('H:i') }}
                        @else
                            <span class="text-warning">Still inside</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Vehicle Number</div>
                    <div class="fw-semibold">{{ $visitorPass->vehicle_number ?: '--' }}</div>
                </div>
                @if($visitorPass->vehicle_pass_number)
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Vehicle Pass Number</div>
                        <div class="fw-semibold">{{ $visitorPass->vehicle_pass_number }}</div>
                    </div>
                @endif
                @if($visitorPass->remark)
                    <div class="col-12">
                        <div class="text-muted small mb-1">Remark</div>
                        <div class="fw-semibold">{{ $visitorPass->remark }}</div>
                    </div>
                @endif
            </div>

            {{-- Attached Document --}}
            @if($docPath)
                <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-paperclip" aria-hidden="true"></i> Attached Document
                </h6>
                <div class="mb-4">
                    @if($docExists)
                        <a href="{{ asset('storage/' . $docPath) }}" target="_blank"
                           class="btn btn-outline-primary rounded-1 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-download" aria-hidden="true"></i>
                            <span>Download Document</span>
                        </a>
                    @else
                        <span class="text-warning small">No file available in storage</span>
                    @endif
                </div>
            @endif

            {{-- Record Details --}}
            <h6 class="text-primary fw-semibold d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-info-circle" aria-hidden="true"></i> Record Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Created By</div>
                    <div class="fw-semibold">{{ $createdByName ?: '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Created Date</div>
                    <div class="fw-semibold">{{ $visitorPass->created_date ? $visitorPass->created_date->format('d-M-Y') : '--' }}</div>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                <a href="{{ route('admin.security.visitor_pass.edit', encrypt($visitorPass->pk)) }}"
                   class="btn btn-primary rounded-1 d-inline-flex align-items-center gap-2 mt-3">
                    <i class="bi bi-pencil" aria-hidden="true"></i> Edit Visitor Pass
                </a>
                <form action="{{ route('admin.security.visitor_pass.delete', encrypt($visitorPass->pk)) }}" method="POST"
                      class="d-inline mt-3" onsubmit="return confirm('Are you sure you want to delete this visitor pass?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-1 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-trash3" aria-hidden="true"></i> Delete Visitor Pass
                    </button>
                </form>
                <a href="{{ route('admin.security.visitor_pass.index') }}"
                   class="btn btn-outline-secondary rounded-1 d-inline-flex align-items-center gap-2 mt-3">
                    <i class="bi bi-x-lg" aria-hidden="true"></i> Close
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
