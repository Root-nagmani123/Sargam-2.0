@extends('admin.layouts.master')

@section('title', ($readOnly ?? false) ? 'View Leave Application' : (($application ?? null) ? 'Edit Leave Application' : 'Apply Leave'))

@section('content')

@include('admin.leave.partials.styles')

@php
    $selectedLeaveType = old('leave_type', $leaveType ?? \App\Models\LeaveApplication::TYPE_PT_EXEMPTION);
    $isPt = $selectedLeaveType === \App\Models\LeaveApplication::TYPE_PT_EXEMPTION;
    $formAction = ($application ?? null)
        ? route('leave.update', $application->pk)
        : route('leave.store');
    $pageTitle = ($readOnly ?? false) ? 'View Leave Application' : (($application ?? null) ? 'Edit Leave Application' : 'Apply Leave');
    $isReadOnly = $readOnly ?? false;
    $isEditing = (bool) ($application ?? null);
    $typeLocked = $isReadOnly || $isEditing;
    $stationedReady = $stationedLeaveConfigured ?? false;
    $upcomingStationed = $upcomingStationedLeave ?? null;
    $activeStationed = $activeStationedLeave ?? null;
    $stationedMinDate = $stationedEarliestFromDate ?? ($activeStationed?->effective_from?->format('Y-m-d')
        ?? ($upcomingStationed?->effective_from?->format('Y-m-d')));
    $ptReady = $ptExemptionConfigured ?? false;
    $upcomingPt = $upcomingPtExemption ?? null;
    $activePt = $activePtExemption ?? null;
    $ptMinDate = $ptEarliestFromDate ?? ($activePt?->effective_from?->format('Y-m-d')
        ?? ($upcomingPt?->effective_from?->format('Y-m-d')));
    $fromDateValue = old('from_date', isset($application) ? $application->from_date?->format('Y-m-d') : '');
    $toDateValue = old('to_date', isset($application) ? $application->to_date?->format('Y-m-d') : '');
    $toDateMin = $fromDateValue ?: ($isPt ? $ptMinDate : $stationedMinDate);
@endphp

<div class="container-fluid leave-module">
    <x-breadcrum :title="$pageTitle" />
    <x-session_message />

    @if(empty($course_is_running))
        <div class="alert alert-warning py-2 small mb-3">
            Your enrolled course has ended. Leave applications use your latest course enrollment.
        </div>
    @endif

    @if($isReadOnly)
        @php
            $applicationStatus = (int) ($application->status ?? -1);
            $isRejectedApplication = $applicationStatus === \App\Models\LeaveApplication::STATUS_REJECTED;
            $isApprovedApplication = $applicationStatus === \App\Models\LeaveApplication::STATUS_APPROVED;
        @endphp
        <div class="alert alert-{{ $isRejectedApplication ? 'danger' : ($isApprovedApplication ? 'success' : 'info') }} py-2 small mb-3">
            Status: <strong>{{ $application->status_label }}</strong>
        </div>

        @if($isRejectedApplication)
            <div class="border border-danger rounded p-3 mb-3 small bg-danger bg-opacity-10">
                <div class="mb-2"><strong>Rejected By:</strong> {{ $application->action_by_faculty_name }}</div>
                @if($application->approved_at)
                    <div class="mb-2"><strong>Rejected On:</strong> {{ $application->approved_at->format('d-m-Y, h:i A') }}</div>
                @endif
                <div><strong>Rejection Reason:</strong> {{ $application->rejection_remarks ?: 'No reason provided.' }}</div>
            </div>
        @endif
    @endif

    @if(! $isReadOnly)
        @if($isPt && ! $ptReady && $upcomingPt)
            <div class="alert alert-warning py-2 small mb-3">
                PT exemption for <strong>{{ $course->course_name }}</strong> will be available from
                <strong>{{ $upcomingPt->effective_from->format('d-m-Y') }}</strong>.
                Please select a start date on or after that date.
            </div>
        @elseif($isPt && ! $ptReady && ! $upcomingPt)
            <div class="alert alert-warning py-2 small mb-3">
                PT exemption is not configured for your course: <strong>{{ $course->course_name }}</strong>.
            </div>
        @elseif($isPt && ($ptCutoffPassedToday ?? false) && $ptCutoffTimeDisplay)
            <div class="alert alert-warning py-2 small mb-3">
                Today's PT timing (<strong>{{ $ptCutoffTimeDisplay }}</strong>) has passed.
                You cannot apply for PT exemption starting today. Please select a future start date.
            </div>
        @endif

        @if(! $isPt && ! $stationedReady && $upcomingStationed)
            <div class="alert alert-warning py-2 small mb-3">
                Stationed leave for <strong>{{ $course->course_name }}</strong> will be available from
                <strong>{{ $upcomingStationed->effective_from->format('d-m-Y') }}</strong>.
                Please select a start date on or after that date.
            </div>
        @elseif(! $isPt && ! $stationedReady && ! $upcomingStationed)
            <div class="alert alert-warning py-2 small mb-3">
                Stationed leave is not configured for your course: <strong>{{ $course->course_name }}</strong>.
            </div>
        @elseif(! $isPt && ($stationedCutoffPassedToday ?? false) && $stationedCutoffTimeDisplay)
            <div class="alert alert-warning py-2 small mb-3">
                Today's PT timing (<strong>{{ $stationedCutoffTimeDisplay }}</strong>) has passed.
                You cannot apply for stationed leave starting today. Please select a future start date.
            </div>
        @endif
    @endif

    <div class="row g-4 leave-apply-layout">
        {{-- Aside: info note + PT balance --}}
        <div class="col-12 col-lg-4 order-2 order-lg-1">
            @php
                $applyCutoffDisplay = $isPt ? ($ptCutoffTimeDisplay ?? null) : ($stationedCutoffTimeDisplay ?? null);
                $leaveTypeLabel = $isPt ? 'PT exemption' : 'Stationed leave';
            @endphp
            <div class="leave-note-card">
                <i class="material-icons material-symbols-rounded note-icon">info</i>
                <p class="leave-note-text">
                    {{ $leaveTypeLabel }} is approved automatically on submit.
                    @if($applyCutoffDisplay)
                        Same day applications are allowed only before {{ $applyCutoffDisplay }}.
                    @endif
                </p>
            </div>

            @if($isPt)
                <div class="pt-balance-box">
                    <div class="pt-balance-head">
                        <i class="material-icons material-symbols-rounded">calendar_month</i>
                        <span>PT Balance</span>
                    </div>
                    <div class="pt-balance-num">{{ number_format((float) ($ptBalance['remaining'] ?? 0), 1) }} Days</div>
                    <div class="pt-balance-sub">As on {{ $ptBalance['as_on'] ?? now()->format('d M Y') }}</div>
                </div>
            @endif
        </div>

        {{-- Form --}}
        <div class="col-12 col-lg-8 order-1 order-lg-2">
    <div class="card leave-apply-card border-0 shadow-sm rounded-3">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" id="leave-apply-form">
                @csrf
                @if($isEditing)
                    @method('PUT')
                @endif

                <input type="hidden" name="leave_type" value="{{ $selectedLeaveType }}">

                <div class="row g-4">

                    {{-- Leave Type --}}
                    <div class="col-12 col-md-6">
                        <label class="leave-grid-label d-block">Leave Type <span class="text-danger">*</span></label>
                        <div class="leave-type-radios" data-apply-url="{{ route('leave.apply') }}">
                            <div class="form-check">
                                <input class="form-check-input leave-type-radio" type="radio" name="leave_type_radio"
                                    id="lt_pt" value="PT_EXEMPTION" {{ $isPt ? 'checked' : '' }}
                                    {{ $typeLocked ? 'disabled' : '' }}>
                                <label class="form-check-label" for="lt_pt">PT Exemption</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input leave-type-radio" type="radio" name="leave_type_radio"
                                    id="lt_stationed" value="STATIONED_LEAVE" {{ ! $isPt ? 'checked' : '' }}
                                    {{ $typeLocked ? 'disabled' : '' }}>
                                <label class="form-check-label" for="lt_stationed">Stationed Leave</label>
                            </div>
                        </div>
                    </div>
                    {{-- Course Name (auto-derived from enrollment, read-only) --}}
                    <div class="col-6">
                        <label for="course_name_display" class="leave-grid-label d-block">Course Name</label>
                        <input type="text" id="course_name_display" class="form-control" readonly
                            value="{{ $course->course_name ?? '' }}">
                    </div>

                    {{-- Nature --}}
                    <div class="col-12 col-md-6">
                        <label for="leave_nature_master_pk" class="leave-grid-label d-block">Nature for Leave <span class="text-danger">*</span></label>
                        <select name="leave_nature_master_pk" id="leave_nature_master_pk" class="form-select" required {{ $isReadOnly ? 'disabled' : '' }}>
                            <option value="">Select Nature</option>
                            @foreach($natures as $nature)
                                <option value="{{ $nature->pk }}"
                                    {{ (string) old('leave_nature_master_pk', $application->leave_nature_master_pk ?? '') === (string) $nature->pk ? 'selected' : '' }}>
                                    {{ $nature->nature_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="col-12 col-md-6">
                        <label for="from_date" class="leave-grid-label d-block">Date From <span class="text-danger">*</span></label>
                        <div class="leave-date-wrap">
                            <input type="date" name="from_date" id="from_date" class="form-control @error('from_date') is-invalid @enderror" required
                                value="{{ $fromDateValue }}"
                                @if($isPt && $ptMinDate) min="{{ $ptMinDate }}" @elseif(! $isPt && $stationedMinDate) min="{{ $stationedMinDate }}" @endif
                                {{ $isReadOnly ? 'readonly' : '' }}>
                            <i class="material-icons material-symbols-rounded leave-date-icon">calendar_month</i>
                        </div>
                        @error('from_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Date To --}}
                    <div class="col-12 col-md-6">
                        <label for="to_date" class="leave-grid-label d-block">Date To <span class="text-danger">*</span></label>
                        <div class="leave-date-wrap">
                            <input type="date" name="to_date" id="to_date" class="form-control @error('to_date') is-invalid @enderror" required
                                value="{{ $toDateValue }}"
                                @if($toDateMin) min="{{ $toDateMin }}" @endif
                                {{ $isReadOnly ? 'readonly' : '' }}>
                            <i class="material-icons material-symbols-rounded leave-date-icon">calendar_month</i>
                        </div>
                        @error('to_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Total Days --}}
                    <div class="col-12 col-md-6">
                        <label for="total_days_display" class="leave-grid-label d-block">Total Days <span class="text-danger">*</span></label>
                        <input type="text" id="total_days_display" class="form-control" readonly
                            value="{{ old('total_days', isset($application) ? number_format((float) $application->total_days, 1) : '0') }}">
                    </div>

                    {{-- Reason --}}
                    <div class="col-12 col-md-6">
                        <label for="reason" class="leave-grid-label d-block">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" rows="3" class="form-control" required
                            placeholder="eg. Enter reason for leave here..."
                            {{ $isReadOnly ? 'readonly' : '' }}>{{ old('reason', $application->reason ?? '') }}</textarea>
                    </div>

                    {{-- Contact Number --}}
                    <div class="col-12 col-md-6">
                        <label for="contact_number" class="leave-grid-label d-block">Contact Number During Exemption <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" id="contact_number" class="form-control @error('contact_number') is-invalid @enderror"
                            maxlength="10" inputmode="numeric" pattern="[6-9][0-9]{9}" required
                            placeholder="eg. 1234567894"
                            value="{{ old('contact_number', $application->contact_number ?? '') }}"
                            {{ $isReadOnly ? 'readonly' : '' }}>
                        @error('contact_number')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Attachments --}}
                    <div class="col-12">
                        <label class="leave-grid-label d-block">Attachments</label>

                        @if(($application ?? null) && $application->attachments->isNotEmpty())
                            <div class="leave-existing-attach mb-2">
                                @foreach($application->attachments as $attachment)
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="material-icons material-symbols-rounded text-secondary" style="font-size:18px;">attach_file</i>
                                        <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">
                                            {{ $attachment->attachment_title ? $attachment->attachment_title . ' — ' : '' }}{{ $attachment->original_file_name }}
                                        </a>
                                        @if(! $isReadOnly)
                                            <input type="hidden" name="existing_attachments[]" value="{{ $attachment->pk }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if(! $isReadOnly)
                            <div id="leave-attachments-wrap">
                                <div class="leave-attachment-row row g-2 align-items-center mb-2" data-index="0">
                                    <div class="col-12 col-md-5">
                                        <input type="text" name="attachments[0][title]"
                                            class="form-control leave-attachment-title"
                                            placeholder="Attachment name (eg. Medical certificate)">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <input type="file" name="attachments[0][file]"
                                            class="form-control leave-attachment-file"
                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    </div>
                                    <div class="col-12 col-md-1 d-flex">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger leave-attachment-remove d-none"
                                            title="Remove attachment">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">close</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="leave-attachment-add">
                                <i class="material-icons material-symbols-rounded" style="font-size:16px;vertical-align:middle;">add</i>
                                Add attachment
                            </button>
                            <div class="form-text">
                                PDF, JPG, JPEG, PNG, DOC, DOCX &middot; max 5 MB each.
                            </div>
                        @endif

                        @php
                            $attachmentErrors = collect($errors->messages())->filter(
                                fn ($_, $key) => str_starts_with($key, 'attachments.')
                            )->flatten();
                        @endphp
                        @if($attachmentErrors->isNotEmpty())
                            <div class="alert alert-danger py-2 small mt-2 mb-0">
                                @foreach($attachmentErrors as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div id="attachment-validation-error" class="alert alert-danger py-2 small d-none mt-2 mb-0"></div>
                    </div>
                </div>

                @if(! $isReadOnly)
                <div class="leave-actions-end">
                    <a href="{{ route('leave.my-leave') }}" class="btn btn-cancel-outline">Cancel</a>
                    <button type="submit" name="submit_action" value="submit" class="btn btn-apply">Apply Leave</button>
                </div>
                @endif
            </form>
        </div>
    </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    const maxSizeBytes = 5 * 1024 * 1024;

    /* ── Leave type switch (navigates, mirrors previous tab behaviour) ── */
    $('.leave-type-radio').on('change', function () {
        if (this.disabled) {
            return;
        }
        const url = $(this).closest('.leave-type-radios').data('applyUrl');
        window.location.href = url + '?leave_type=' + encodeURIComponent(this.value);
    });

    /* ── Attachment validation ── */
    function validateAttachmentFile(file) {
        if (!file) {
            return null;
        }
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (!allowedExtensions.includes(ext)) {
            return 'File "' + file.name + '" is not allowed. Use PDF, JPG, JPEG, PNG, DOC, or DOCX.';
        }
        if (file.size > maxSizeBytes) {
            return 'File "' + file.name + '" exceeds the 5 MB size limit.';
        }
        return null;
    }

    function showAttachmentError(message) {
        $('#attachment-validation-error').text(message).removeClass('d-none');
    }

    function clearAttachmentError() {
        $('#attachment-validation-error').addClass('d-none').text('');
        $('.leave-attachment-file').removeClass('is-invalid');
    }

    $(document).on('change', '.leave-attachment-file', function () {
        clearAttachmentError();
        const file = this.files && this.files[0];
        if (!file) {
            return;
        }
        const error = validateAttachmentFile(file);
        if (error) {
            $(this).addClass('is-invalid').val('');
            showAttachmentError(error);
        }
    });

    /* ── Add / remove attachment rows ── */
    let attachmentIndex = $('#leave-attachments-wrap .leave-attachment-row').length;

    function refreshRemoveButtons() {
        const $rows = $('#leave-attachments-wrap .leave-attachment-row');
        $rows.find('.leave-attachment-remove').toggleClass('d-none', $rows.length <= 1);
    }

    $('#leave-attachment-add').on('click', function () {
        const index = attachmentIndex++;
        const row =
            '<div class="leave-attachment-row row g-2 align-items-center mb-2" data-index="' + index + '">' +
                '<div class="col-12 col-md-5">' +
                    '<input type="text" name="attachments[' + index + '][title]" ' +
                        'class="form-control leave-attachment-title" ' +
                        'placeholder="Attachment name (eg. Medical certificate)">' +
                '</div>' +
                '<div class="col-12 col-md-6">' +
                    '<input type="file" name="attachments[' + index + '][file]" ' +
                        'class="form-control leave-attachment-file" ' +
                        'accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">' +
                '</div>' +
                '<div class="col-12 col-md-1 d-flex">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger leave-attachment-remove" title="Remove attachment">' +
                        '<i class="material-icons material-symbols-rounded" style="font-size:18px;">close</i>' +
                    '</button>' +
                '</div>' +
            '</div>';
        $('#leave-attachments-wrap').append(row);
        refreshRemoveButtons();
    });

    $(document).on('click', '.leave-attachment-remove', function () {
        $(this).closest('.leave-attachment-row').remove();
        refreshRemoveButtons();
    });

    /* ── Date sync + total days ── */
    function syncEndDateMin() {
        const from = $('#from_date').val();
        const $toDate = $('#to_date');
        if (!from) {
            return;
        }
        $toDate.attr('min', from);
        const to = $toDate.val();
        if (!to || to < from) {
            $toDate.val(from);
        }
    }

    function updateTotalDays() {
        const from = $('#from_date').val();
        const to = $('#to_date').val();
        if (!from || !to) {
            $('#total_days_display').val('0');
            return;
        }
        const start = new Date(from + 'T00:00:00');
        const end = new Date(to + 'T00:00:00');
        if (end < start) {
            $('#total_days_display').val('0');
            return;
        }
        const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
        $('#total_days_display').val(diff);
    }

    $('#from_date').on('change', function () {
        syncEndDateMin();
        updateTotalDays();
    });
    $('#to_date').on('change', updateTotalDays);
    syncEndDateMin();
    updateTotalDays();

    /* ── Block submit on invalid attachment ── */
    $('#leave-apply-form').on('submit', function (e) {
        clearAttachmentError();
        let firstError = null;
        $('.leave-attachment-file').each(function () {
            const file = this.files && this.files[0];
            const error = validateAttachmentFile(file);
            if (error) {
                $(this).addClass('is-invalid');
                if (!firstError) {
                    firstError = error;
                }
            }
        });
        if (firstError) {
            showAttachmentError(firstError);
            e.preventDefault();
        }
    });
});
</script>
@endpush
