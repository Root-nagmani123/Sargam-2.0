@extends('admin.layouts.master')

@section('title', 'View Leave Application')

@section('content')

@include('admin.leave.faculty_approval.partials.styles')

@php
    use App\Models\LeaveApplication;

    $studentName = app(\App\Services\FacultyLeaveApprovalService::class)->studentDisplayName($application->student);
    $otCode = $application->student->generated_OT_code ?? null;

    $statusMod = match ((int) $application->status) {
        LeaveApplication::STATUS_APPROVED => 'approved',
        LeaveApplication::STATUS_REJECTED => 'rejected',
        default => 'pending',
    };
    $isPending = (int) $application->status === LeaveApplication::STATUS_PENDING;

    $initials = collect(explode(' ', trim($studentName)))
        ->filter()->take(2)
        ->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))
        ->implode('') ?: 'OT';
@endphp

<style>
    /* ── View Leave Application (detail) ── */
    .fla-show .fla-head {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem 1.25rem;
        background: linear-gradient(135deg, #f5f8fc 0%, #eef3fa 100%);
        border-bottom: 1px solid #e4e7ec;
    }
    .fla-show .fla-avatar {
        flex: 0 0 auto;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: #fff;
        background: linear-gradient(135deg, #004a93, #0066cc);
        box-shadow: 0 4px 10px rgba(0, 74, 147, 0.25);
    }
    .fla-show .fla-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: #101828;
        margin: 0;
        line-height: 1.2;
    }
    .fla-show .fla-sub {
        font-size: 0.85rem;
        color: #667085;
        margin-top: 0.15rem;
    }
    .fla-show .fla-sub .fla-otcode {
        font-weight: 600;
        color: #004a93;
    }
    .fla-show .fla-status {
        margin-left: auto;
        border-radius: 999px;
        white-space: nowrap;
    }

    /* Summary tiles */
    .fla-show .fla-tiles {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }
    .fla-show .fla-tile {
        border: 1px solid #eaecf0;
        border-radius: 10px;
        padding: 0.75rem 0.9rem;
        background: #fcfcfd;
    }
    .fla-show .fla-tile-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #98a2b3;
        font-weight: 600;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .fla-show .fla-tile-label .material-symbols-rounded { font-size: 15px; }
    .fla-show .fla-tile-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: #101828;
        word-break: break-word;
    }
    .fla-show .fla-tile-value.fla-strong { color: #004a93; }

    .fla-show .fla-block { margin-top: 1.25rem; }
    .fla-show .fla-block-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #98a2b3;
        font-weight: 600;
        margin-bottom: 0.4rem;
    }
    .fla-show .fla-reason {
        border: 1px solid #eaecf0;
        border-left: 3px solid #004a93;
        border-radius: 8px;
        background: #fcfcfd;
        padding: 0.75rem 0.9rem;
        color: #344054;
        font-size: 0.925rem;
        white-space: pre-line;
    }

    /* Attachments */
    .fla-show .fla-attach-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 0.65rem;
    }
    .fla-show .fla-attach {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        border: 1px solid #eaecf0;
        border-radius: 10px;
        padding: 0.6rem 0.75rem;
        text-decoration: none;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .fla-show .fla-attach:hover {
        border-color: #004a93;
        box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
        transform: translateY(-1px);
    }
    .fla-show .fla-attach-ic {
        flex: 0 0 auto;
        width: 34px; height: 34px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #eef3fa; color: #004a93;
    }
    .fla-show .fla-attach-ic .material-symbols-rounded { font-size: 19px; }
    .fla-show .fla-attach-meta { min-width: 0; flex: 1 1 auto; }
    .fla-show .fla-attach-name {
        display: block;
        font-size: 0.875rem; font-weight: 600; color: #101828;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .fla-show .fla-attach-sub { font-size: 0.75rem; color: #98a2b3; }
    .fla-show .fla-attach .fla-attach-dl { color: #98a2b3; }
    .fla-show .fla-attach:hover .fla-attach-dl { color: #004a93; }

    /* Decision / side panel */
    .fla-show .fla-side-card { position: sticky; top: 1rem; }
    .fla-show .fla-side-title {
        font-size: 0.95rem; font-weight: 700; color: #101828; margin-bottom: 0.25rem;
    }
    .fla-show .fla-side-note { font-size: 0.82rem; color: #667085; margin-bottom: 1rem; }
    .fla-show .fla-decision-btn {
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
        font-weight: 600; border-radius: 9px; padding: 0.6rem 1rem; border: 1px solid transparent;
    }
    .fla-show .fla-decision-btn .material-symbols-rounded { font-size: 19px; }
    .fla-show .fla-btn-approve { background: #12b76a; color: #fff; }
    .fla-show .fla-btn-approve:hover { background: #027a48; color: #fff; }
    .fla-show .fla-btn-reject { background: #fff; color: #b42318; border-color: #fda29b; }
    .fla-show .fla-btn-reject:hover { background: #fef3f2; color: #b42318; }

    .fla-show .fla-decision-summary {
        border-radius: 10px; padding: 0.9rem 1rem; border: 1px solid;
    }
    .fla-show .fla-decision-summary.is-approved { background: #ecfdf3; border-color: #a6f4c5; }
    .fla-show .fla-decision-summary.is-rejected { background: #fef3f2; border-color: #fecdca; }
    .fla-show .fla-decision-head {
        display: flex; align-items: center; gap: 0.45rem; font-weight: 700; margin-bottom: 0.5rem;
    }
    .fla-show .fla-decision-summary.is-approved .fla-decision-head { color: #027a48; }
    .fla-show .fla-decision-summary.is-rejected .fla-decision-head { color: #b42318; }
    .fla-show .fla-decision-row { font-size: 0.85rem; color: #475467; margin-bottom: 0.2rem; }
    .fla-show .fla-decision-row strong { color: #101828; font-weight: 600; }

    @media (max-width: 575.98px) {
        .fla-show .fla-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fla-show .fla-head { flex-wrap: wrap; }
        .fla-show .fla-status { margin-left: 0; }
    }
</style>

<div class="container-fluid py-3 faculty-leave-approval-page fla-show">
    <x-breadcrum title="View Leave Application" />
    <x-session_message />

    <div class="row g-3">
        {{-- Main: application details --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="fla-head">
                    <span class="fla-avatar">{{ $initials }}</span>
                    <div class="min-w-0">
                        <h1 class="fla-name">{{ $studentName }}</h1>
                        <div class="fla-sub">
                            @if($otCode)<span class="fla-otcode">{{ $otCode }}</span> &middot; @endif
                            {{ $application->course->course_name ?? 'Course —' }}
                        </div>
                    </div>
                    <span class="fla-status approval-status approval-status--{{ $statusMod }}">
                        {{ $application->status_label }}
                    </span>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="fla-tiles">
                        <div class="fla-tile">
                            <div class="fla-tile-label"><span class="material-icons material-symbols-rounded">category</span>Leave Type</div>
                            <div class="fla-tile-value fla-strong">{{ $application->leave_type_label }}</div>
                        </div>
                        <div class="fla-tile">
                            <div class="fla-tile-label"><span class="material-icons material-symbols-rounded">label</span>Nature</div>
                            <div class="fla-tile-value">{{ $application->nature->nature_name ?? '-' }}</div>
                        </div>
                        <div class="fla-tile">
                            <div class="fla-tile-label"><span class="material-icons material-symbols-rounded">event_available</span>Total Days</div>
                            <div class="fla-tile-value">{{ number_format((float) $application->total_days, 0) }}</div>
                        </div>
                        <div class="fla-tile">
                            <div class="fla-tile-label"><span class="material-icons material-symbols-rounded">calendar_month</span>From Date</div>
                            <div class="fla-tile-value">{{ $application->from_date?->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="fla-tile">
                            <div class="fla-tile-label"><span class="material-icons material-symbols-rounded">calendar_month</span>To Date</div>
                            <div class="fla-tile-value">{{ $application->to_date?->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="fla-tile">
                            <div class="fla-tile-label"><span class="material-icons material-symbols-rounded">call</span>Contact</div>
                            <div class="fla-tile-value">{{ $application->contact_number ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="fla-block">
                        <div class="fla-block-label">Reason</div>
                        <div class="fla-reason">{{ $application->reason ?: '—' }}</div>
                    </div>

                    @if($application->attachments->isNotEmpty())
                    <div class="fla-block">
                        <div class="fla-block-label">Attachments ({{ $application->attachments->count() }})</div>
                        <div class="fla-attach-grid">
                            @foreach($application->attachments as $attachment)
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" rel="noopener" class="fla-attach">
                                    <span class="fla-attach-ic"><span class="material-icons material-symbols-rounded">description</span></span>
                                    <span class="fla-attach-meta">
                                        <span class="fla-attach-name">{{ $attachment->attachment_title ?: ($attachment->original_file_name ?? 'Attachment') }}</span>
                                        <span class="fla-attach-sub">{{ $attachment->original_file_name ?? 'Download' }}</span>
                                    </span>
                                    <span class="material-icons material-symbols-rounded fla-attach-dl">download</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Side: decision panel --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 fla-side-card">
                <div class="card-body p-3 p-md-4">
                    @if($isPending)
                        <div class="fla-side-title">Review Decision</div>
                        <p class="fla-side-note">Approve or reject this leave request. The applicant is notified of your decision.</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="fla-decision-btn fla-btn-approve" id="flaApprove" data-id="{{ $application->pk }}">
                                <span class="material-icons material-symbols-rounded">check_circle</span> Approve
                            </button>
                            <button type="button" class="fla-decision-btn fla-btn-reject" id="flaReject" data-id="{{ $application->pk }}">
                                <span class="material-icons material-symbols-rounded">cancel</span> Reject
                            </button>
                        </div>
                    @else
                        <div class="fla-decision-summary is-{{ $statusMod }}">
                            <div class="fla-decision-head">
                                <span class="material-icons material-symbols-rounded">{{ $statusMod === 'approved' ? 'check_circle' : 'cancel' }}</span>
                                {{ $application->status_label }}
                            </div>
                            <div class="fla-decision-row"><strong>By:</strong> {{ $application->action_by_faculty_name }}</div>
                            @if($application->approved_at)
                                <div class="fla-decision-row"><strong>On:</strong> {{ $application->approved_at->format('d M Y, h:i A') }}</div>
                            @endif
                            @if($statusMod === 'rejected' && $application->rejection_remarks)
                                <div class="fla-decision-row mt-2"><strong>Remarks:</strong><br>{{ $application->rejection_remarks }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const approveUrl = "{{ route('faculty.leave-approval.approve', ':id') }}";
    const rejectUrl  = "{{ route('faculty.leave-approval.reject', ':id') }}";
    const listUrl    = "{{ route('faculty.leave-approval.index') }}";
    const csrf       = "{{ csrf_token() }}";

    $('#flaApprove').on('click', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Approve leave?',
            text: 'This leave application will be approved.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, approve',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post(approveUrl.replace(':id', id), { _token: csrf })
                .done(function (res) {
                    toastr.success(res.message || 'Leave approved.');
                    setTimeout(function () { window.location.href = listUrl; }, 700);
                })
                .fail(function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Approval failed.');
                });
        });
    });

    $('#flaReject').on('click', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Reject leave?',
            input: 'textarea',
            inputLabel: 'Remarks (optional)',
            inputPlaceholder: 'Reason for rejection...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Reject',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post(rejectUrl.replace(':id', id), { _token: csrf, rejection_remarks: result.value || '' })
                .done(function (res) {
                    toastr.success(res.message || 'Leave rejected.');
                    setTimeout(function () { window.location.href = listUrl; }, 700);
                })
                .fail(function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Rejection failed.');
                });
        });
    });
});
</script>
@endpush
