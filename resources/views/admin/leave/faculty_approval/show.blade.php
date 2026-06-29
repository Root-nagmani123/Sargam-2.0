@extends('admin.layouts.master')

@section('title', 'View Leave Application')

@section('content')

@include('admin.leave.faculty_approval.partials.styles')

@php
    $studentName = app(\App\Services\FacultyLeaveApprovalService::class)->studentDisplayName($application->student);
@endphp

<div class="container-fluid py-3 faculty-leave-approval-page">
    <x-breadcrum title="View Leave Application" />
    <x-session_message />

    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1 fw-semibold">Leave Application Details</h2>
                    <div class="small text-muted">{{ $studentName }}</div>
                </div>
                <a href="{{ route('faculty.leave-approval.index') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Participant</div>
                        <div class="fw-semibold">{{ $studentName }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Course</div>
                        <div class="fw-semibold">{{ $application->course->course_name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Leave Type</div>
                        <div class="fw-semibold">{{ $application->leave_type_label }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Status</div>
                        <span class="badge rounded-pill {{ $application->status_badge_class }}">{{ $application->status_label }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Nature of Leave</div>
                        <div class="fw-semibold">{{ $application->nature->nature_name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">From Date</div>
                        <div class="fw-semibold">{{ $application->from_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">To Date</div>
                        <div class="fw-semibold">{{ $application->to_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Total Days</div>
                        <div class="fw-semibold">{{ number_format((float) $application->total_days, 0) }}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3">
                        <div class="small text-muted mb-1">Reason</div>
                        <div>{{ $application->reason ?? '-' }}</div>
                    </div>
                </div>
                @if($application->contact_number)
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Contact During Leave</div>
                        <div>{{ $application->contact_number }}</div>
                    </div>
                </div>
                @endif
            </div>

            @if($application->attachments->isNotEmpty())
            <div class="mt-4">
                <h3 class="h6 fw-semibold mb-2">Attachments</h3>
                <ul class="list-group list-group-flush border rounded">
                    @foreach($application->attachments as $attachment)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $attachment->attachment_title ?? 'Attachment' }}</span>
                            <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Download</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
